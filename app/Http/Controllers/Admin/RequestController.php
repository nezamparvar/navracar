<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\LeadActivity;
use App\Models\MessageTemplate;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use App\Services\LeadLifecycleService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public const STATUSES = ['باز', 'در حال پیگیری', 'فروخته شد', 'بسته - موفق', 'بسته - ناموفق'];

    public const COUNTRIES = [
        'ایران', 'امارات متحده عربی', 'ترکیه', 'عراق', 'افغانستان', 'آلمان', 'کانادا',
        'آمریکا', 'انگلستان', 'استرالیا', 'سوئد', 'هلند', 'فرانسه', 'سایر',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = QuoteRequest::query()->withoutTrashed()->with(['assignee']);

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        } elseif ((string) $request->string('assigned') === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($request->filled('assigned') && (string) $request->string('assigned') !== 'all') {
            $query->where('assigned_to', (int) $request->input('assigned'));
        }

        if ($q = (string) $request->string('q', '')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('car_label', 'like', "%{$q}%");
            });
        }

        if ($name = (string) $request->string('name', '')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($phone = (string) $request->string('phone', '')) {
            $query->where('phone', 'like', "%{$phone}%");
        }
        if ($email = (string) $request->string('email', '')) {
            $query->where('email', 'like', "%{$email}%");
        }
        if ($carLabel = (string) $request->string('car_label', '')) {
            $query->where('car_label', 'like', "%{$carLabel}%");
        }

        if ($from = (string) $request->string('from', '')) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = (string) $request->string('to', '')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        if ($stage = (string) $request->string('stage', '')) {
            $query->where('current_stage_id', (int) $stage);
        }

        $showArchived = $request->boolean('show_archived', false);
        if (! $showArchived) {
            $query->where('is_archived', false);
        }

        $showAll = $request->boolean('show_all', false);
        if (! $showAll && ! $request->filled('status')) {
            $query->whereIn('follow_up_status', ['باز', 'در حال پیگیری']);
        } elseif ($status = (string) $request->string('status', '')) {
            $query->where('follow_up_status', $status);
        }

        $rows = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $staffList = $user->isAdmin() ? AdminUser::orderBy('username')->get() : collect();
        $pipelineStages = PipelineStage::orderBy('order')->get();

        return view('admin.requests.index', [
            'pageTitle' => 'درخواست‌های استعلام قیمت (CRM)',
            'rows' => $rows,
            'staffList' => $staffList,
            'statuses' => self::STATUSES,
            'pipelineStages' => $pipelineStages,
            'filters' => $request->only(['q', 'name', 'phone', 'email', 'car_label', 'from', 'to', 'status', 'stage', 'assigned', 'show_all', 'show_archived']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $staffList = $user->isAdmin() ? AdminUser::orderBy('username')->get() : collect();

        return view('admin.requests.create', [
            'pageTitle' => 'ثبت دستی مشتری تماس‌گرفته',
            'staffList' => $staffList,
            'cities' => self::CITIES,
            'countries' => self::COUNTRIES,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email'],
            'source' => ['nullable', 'string', 'max:50'],
            'car_label' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'budget_range' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'total_with_profit' => ['nullable', 'string'],
            'next_call_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $assignedTo = ($user->isAdmin() && ! empty($data['assigned_to'])) ? (int) $data['assigned_to'] : $user->id;
        $total = isset($data['total_with_profit']) ? (float) preg_replace('/[^0-9.]/', '', $data['total_with_profit']) : 0;

        $lead = QuoteRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'car_label' => $data['car_label'] ?? null,
            'category' => $data['category'] ?? null,
            'breakdown_json' => '[]',
            'totals_json' => '{}',
            'total_with_profit' => $total,
            'email_sent' => false,
            'source' => $data['source'] ?? 'تماس تلفنی',
            'budget_range' => $data['budget_range'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'assigned_to' => $assignedTo,
            'created_by' => $user->id,
            'follow_up_status' => 'باز',
            'next_call_date' => $data['next_call_date'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $user->id,
            'activity_type' => 'note',
            'note' => 'ثبت دستی توسط پنل مدیریت (منبع: '.($data['source'] ?? 'تماس تلفنی').')',
        ]);

        ActivityLogger::info('ثبت دستی مشتری در پنل', ['id' => $lead->id, 'name' => $lead->name, 'phone' => $lead->phone]);

        return response()->json(['success' => true, 'message' => 'مشتری با موفقیت ثبت شد.', 'id' => $lead->id]);
    }

    public function show(Request $request, QuoteRequest $lead)
    {
        $user = $request->user();
        $this->authorize('view', $lead);

        $staffList = $user->isAdmin() ? AdminUser::orderBy('username')->get() : collect();
        $templates = MessageTemplate::where('is_active', true)->orderBy('category')->orderBy('id')->get();
        $activities = $lead->activities()->with('adminUser')->get();

        return view('admin.requests.show', [
            'pageTitle' => 'جزئیات درخواست #'.$lead->id,
            'lead' => $lead,
            'staffList' => $staffList,
            'templates' => $templates,
            'activities' => $activities,
            'statuses' => self::STATUSES,
        ]);
    }

    public function assign(Request $request, QuoteRequest $lead)
    {
        $this->authorize('assign', $lead);

        $data = $request->validate(['assigned_to' => ['nullable', 'integer']]);
        $newAssignee = $data['assigned_to'] ?? null;
        $lead->update(['assigned_to' => $newAssignee]);

        $assigneeName = '—';
        if ($newAssignee) {
            $u = AdminUser::find($newAssignee);
            $assigneeName = $u?->displayName() ?? '—';
        }

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $request->user()->id,
            'activity_type' => 'assign',
            'note' => 'الحاق به: '.$assigneeName,
        ]);

        return back()->with('success', 'الحاق با موفقیت انجام شد.');
    }

    public function temperature(Request $request, QuoteRequest $lead)
    {
        $this->authorize('updateTemperature', $lead);

        $data = $request->validate(['temperature' => ['required', Rule::in(['hot', 'warm', 'cold'])]]);
        $lead->update(['temperature' => $data['temperature']]);

        $labels = ['hot' => 'داغ', 'warm' => 'معمولی', 'cold' => 'سرد'];
        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $request->user()->id,
            'activity_type' => 'note',
            'note' => 'دمای سرنخ به «'.$labels[$data['temperature']].'» تغییر کرد',
        ]);

        return back()->with('success', 'دمای سرنخ به‌روزرسانی شد.');
    }

    public function status(Request $request, QuoteRequest $lead)
    {
        $this->authorize('updateStatus', $lead);

        $data = $request->validate([
            'follow_up_status' => ['nullable', Rule::in(self::STATUSES)],
            'note' => ['nullable', 'string'],
            'next_call_date' => ['nullable', 'date'],
        ]);

        $newStatus = $data['follow_up_status'] ?? null;
        $note = trim($data['note'] ?? '');
        $lifecycle = new LeadLifecycleService();

        if ($newStatus) {
            $lifecycle->updateStatus($lead, $newStatus, $request->user()->id, $note ?: null);
        } elseif ($note !== '') {
            LeadActivity::create([
                'request_id' => $lead->id,
                'admin_user_id' => $request->user()->id,
                'activity_type' => 'note',
                'note' => $note,
            ]);
        }

        $previousNextCall = optional($lead->next_call_date)->toDateString();
        $newNextCall = $data['next_call_date'] ?? null;
        $lead->update(['next_call_date' => $newNextCall]);

        if ($newNextCall && $newNextCall !== $previousNextCall) {
            LeadActivity::create([
                'request_id' => $lead->id,
                'admin_user_id' => $request->user()->id,
                'activity_type' => 'note',
                'note' => 'تاریخ تماس بعدی به '.$newNextCall.' تنظیم شد',
            ]);
        }

        return back()->with('success', 'به‌روزرسانی ثبت شد.');
    }

    public function close(Request $request, QuoteRequest $lead)
    {
        $this->authorize('close', $lead);

        $data = $request->validate([
            'status' => ['required', Rule::in(['بسته - موفق', 'بسته - ناموفق'])],
        ]);

        $lifecycle = new LeadLifecycleService();
        if ($data['status'] === 'بسته - موفق') {
            $lifecycle->closeSuccessfully($lead, $request->user()->id);
        } else {
            $lifecycle->closeUnsuccessfully($lead, $request->user()->id);
        }

        return back()->with('success', 'درخواست با موفقیت بسته شد.');
    }

    public function archive(Request $request, QuoteRequest $lead)
    {
        $this->authorize('archive', $lead);

        $lifecycle = new LeadLifecycleService();
        $lifecycle->archive($lead, $request->user()->id);

        return back()->with('success', 'درخواست با موفقیت بایگانی شد.');
    }

    public function unarchive(Request $request, QuoteRequest $lead)
    {
        $this->authorize('unarchive', $lead);

        $lifecycle = new LeadLifecycleService();
        $lifecycle->unarchive($lead, $request->user()->id);

        return back()->with('success', 'درخواست با موفقیت از بایگانی خارج شد.');
    }

    public function destroy(Request $request, QuoteRequest $lead)
    {
        $this->authorize('delete', $lead);

        $leadName = $lead->name;
        $lead->delete();

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $request->user()->id,
            'activity_type' => 'note',
            'note' => 'درخواست حذف شد',
        ]);

        ActivityLogger::error('حذف درخواست از سیستم', ['id' => $lead->id, 'name' => $leadName]);

        return back()->with('success', 'درخواست با موفقیت حذف شد.');
    }

    public function deletedIndex(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $query = QuoteRequest::onlyTrashed();

        if ($q = (string) $request->string('q', '')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('car_label', 'like', "%{$q}%");
            });
        }

        $rows = $query->orderByDesc('deleted_at')->paginate(15)->withQueryString();

        return view('admin.requests.deleted', [
            'pageTitle' => 'درخواست‌های حذف‌شده',
            'rows' => $rows,
            'filters' => $request->only(['q']),
        ]);
    }

    public function restore(Request $request, QuoteRequest $deletedLead)
    {
        $this->authorize('restore', $deletedLead);

        $deletedLead->restore();

        LeadActivity::create([
            'request_id' => $deletedLead->id,
            'admin_user_id' => $request->user()->id,
            'activity_type' => 'note',
            'note' => 'درخواست بازیابی شد',
        ]);

        ActivityLogger::info('بازیابی درخواست حذف‌شده', ['id' => $deletedLead->id, 'name' => $deletedLead->name]);

        return back()->with('success', 'درخواست با موفقیت بازیابی شد.');
    }

    public function forceDelete(Request $request, QuoteRequest $deletedLead)
    {
        $this->authorize('forceDelete', $deletedLead);

        $leadId = $deletedLead->id;
        $leadName = $deletedLead->name;
        $deletedLead->forceDelete();

        ActivityLogger::error('حذف دائمی درخواست', ['id' => $leadId, 'name' => $leadName]);

        return back()->with('success', 'درخواست به‌طور دائمی حذف شد.');
    }

    public const CITIES = [
        'تهران', 'کرج', 'مشهد', 'اصفهان', 'شیراز', 'تبریز', 'اهواز', 'قم', 'کرمانشاه', 'ارومیه',
        'رشت', 'زاهدان', 'کرمان', 'اراک', 'یزد', 'اردبیل', 'بندرعباس', 'قزوین', 'ساری', 'همدان', 'سایر',
    ];
}
