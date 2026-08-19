<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\CalendarEvent;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $view = in_array($request->string('view', 'week')->value(), ['day', 'week', 'list'], true)
            ? $request->string('view', 'week')->value()
            : 'week';

        $anchor = $request->filled('date')
            ? Carbon::parse($request->string('date')->value())
            : now();

        $rangeStart = match ($view) {
            'day' => $anchor->copy()->startOfDay(),
            'week' => $anchor->copy()->startOfWeek(Carbon::SATURDAY),
            'list' => now()->startOfDay(),
        };
        $rangeEnd = match ($view) {
            'day' => $anchor->copy()->endOfDay(),
            'week' => $anchor->copy()->startOfWeek(Carbon::SATURDAY)->addDays(6)->endOfDay(),
            'list' => now()->addDays(14)->endOfDay(),
        };

        $events = CalendarEvent::query()
            ->forUser($user)
            ->with(['quoteRequest', 'assignee'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->orderBy('starts_at')
            ->get();

        return view('admin.calendar.index', [
            'pageTitle' => 'تقویم جلسات و تماس‌ها',
            'pageSubtitle' => 'برنامه جلسات مشاوره، تماس‌های پیگیری و پرداخت، و جلسات تحویل.',
            'view' => $view,
            'anchor' => $anchor,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'events' => $events,
            'types' => CalendarEvent::TYPES,
            'staffList' => $user->isAdmin() ? AdminUser::orderBy('username')->get() : collect([$user]),
            'requests' => QuoteRequest::query()
                ->when(! $user->isAdmin(), fn ($q) => $q->where('assigned_to', $user->id))
                ->latest('created_at')->limit(200)->get(['id', 'name', 'car_label']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $this->validated($request, $user);

        $this->assertNoOverlap($data['assigned_to'], $data['starts_at'], $data['ends_at']);

        CalendarEvent::create([
            ...$data,
            'created_by' => $user->id,
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        return back()->with('success', 'رویداد با موفقیت ثبت شد.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $this->authorize('reschedule', $event);
        $user = $request->user();
        $data = $this->validated($request, $user);

        $this->assertNoOverlap($data['assigned_to'], $data['starts_at'], $data['ends_at'], $event->id);

        $event->update($data);

        return back()->with('success', 'رویداد بروزرسانی شد.');
    }

    public function complete(Request $request, CalendarEvent $event)
    {
        $this->authorize('complete', $event);
        $event->update(['status' => CalendarEvent::STATUS_COMPLETED]);

        return back()->with('success', 'رویداد به‌عنوان انجام‌شده علامت خورد.');
    }

    public function cancel(Request $request, CalendarEvent $event)
    {
        $this->authorize('cancel', $event);
        $event->update(['status' => CalendarEvent::STATUS_CANCELLED]);

        return back()->with('success', 'رویداد لغو شد.');
    }

    private function validated(Request $request, AdminUser $user): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(CalendarEvent::TYPES))],
            'title' => ['nullable', 'string', 'max:255'],
            'quote_request_id' => ['nullable', 'integer', 'exists:quote_requests,id'],
            'assigned_to' => ['required', 'integer', 'exists:admin_users,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $user->isAdmin() && (int) $data['assigned_to'] !== $user->id) {
            throw ValidationException::withMessages(['assigned_to' => 'کارشناس فروش فقط می‌تواند برای خودش رویداد ثبت کند.']);
        }

        return $data;
    }

    private function assertNoOverlap(int $assignedTo, string $startsAt, string $endsAt, ?int $excludeId = null): void
    {
        $conflict = CalendarEvent::overlapping($assignedTo, Carbon::parse($startsAt), Carbon::parse($endsAt), $excludeId)->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'در این بازه زمانی رویداد دیگری برای همین مسئول ثبت شده است: '.$conflict->displayTitle(),
            ]);
        }
    }
}
