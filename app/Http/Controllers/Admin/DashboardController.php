<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use App\Models\CalendarEvent;
use App\Models\CarListing;
use App\Models\ImportQueueItem;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Models\VinCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $baseScope = fn ($query) => $isAdmin ? $query : $query->where('assigned_to', $user->id);

        $activeStageIds = PipelineStage::whereIn('slug', ['delivered', 'lost'])->pluck('id');

        // KPI metrics — exactly 4, matching DESIGN_SPEC.md §5
        $newRequests = $baseScope(QuoteRequest::query())->whereDate('created_at', today())->count();
        $underFollowUp = $baseScope(QuoteRequest::query())
            ->where('is_archived', false)
            ->where(function ($q) use ($activeStageIds) {
                $q->whereNull('current_stage_id')->orWhereNotIn('current_stage_id', $activeStageIds);
            })
            ->count();
        $activeListings = $isAdmin ? CarListing::where('status', 'published')->count() : 0;
        $failedImports = $isAdmin ? ImportQueueItem::where('status', 'failed')->count() : 0;

        // Additional metrics for admin-only widgets (dashboard content below KPIs)
        $todayCalcs = $isAdmin ? CalculationLog::whereDate('created_at', today())->count() : 0;
        $todayVin = $isAdmin ? VinCheck::whereDate('created_at', today())->count() : 0;
        $unassignedCount = $isAdmin ? QuoteRequest::whereNull('assigned_to')->count() : 0;
        $callsToday = $baseScope(QuoteRequest::query())->whereDate('next_call_date', today())->count();
        $hotLeads = $baseScope(QuoteRequest::query())
            ->where('temperature', 'hot')
            ->where(function ($q) use ($activeStageIds) {
                $q->whereNull('current_stage_id')->orWhereNotIn('current_stage_id', $activeStageIds);
            })
            ->count();

        $weekRequests = $baseScope(QuoteRequest::query())->where('created_at', '>=', now()->subDays(7)->startOfDay())->count();
        $monthRequests = $baseScope(QuoteRequest::query())->where('created_at', '>=', now()->subDays(30)->startOfDay())->count();
        $avgTotal = $baseScope(QuoteRequest::query())->where('total_with_profit', '>', 0)->avg('total_with_profit');

        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $daily[$d] = ['date' => $d, 'requests' => 0, 'calcs' => 0];
        }
        $baseScope(QuoteRequest::query())
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->get()
            ->each(function ($r) use (&$daily) {
                if (isset($daily[$r->d])) {
                    $daily[$r->d]['requests'] = (int) $r->c;
                }
            });

        if ($isAdmin) {
            CalculationLog::selectRaw('DATE(created_at) d, COUNT(*) c')
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->groupBy('d')->get()
                ->each(function ($r) use (&$daily) {
                    if (isset($daily[$r->d])) {
                        $daily[$r->d]['calcs'] = (int) $r->c;
                    }
                });
        }

        $catDist = $isAdmin ? CalculationLog::select('category', DB::raw('COUNT(*) c'))
            ->whereNotNull('category')->where('category', '!=', '')
            ->groupBy('category')->orderByDesc('c')->get() : collect();

        $topCars = $isAdmin ? CalculationLog::select('car_label', DB::raw('COUNT(*) c'))
            ->whereNotNull('car_label')->whereNotIn('car_label', ['', 'مشخص نشده'])
            ->groupBy('car_label')->orderByDesc('c')->limit(8)->get() : collect();

        $recentRequests = $baseScope(QuoteRequest::query())
            ->latest('created_at')->limit(8)
            ->get(['id', 'created_at', 'name', 'phone', 'car_label', 'total_with_profit', 'email_sent']);

        // Today's rates + import status — admin-only, matches the reference dashboard's
        // "نرخ‌های امروز" and "وضعیت ایمپورت" widgets, real data (Setting/ImportQueueItem),
        // no fabricated figures.
        $todayRates = $isAdmin ? [
            'aed' => (float) Setting::get(Setting::FREE_RATE),
            'usd' => (float) Setting::get(Setting::FREE_RATE) * (float) Setting::get(Setting::USD_TO_AED_RATE),
        ] : null;

        $importStatus = $isAdmin ? ImportQueueItem::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as succeeded,
                SUM(CASE WHEN status IN ('pending', 'captured', 'parsed', 'needs_review', 'image_importing') THEN 1 ELSE 0 END) as queued,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            ")
            ->first() : null;

        // This week's calendar events, condensed for the dashboard widget — same
        // CalendarEvent data as the full /admin/calendar page.
        $weekEvents = CalendarEvent::query()
            ->forUser($user)
            ->with(['quoteRequest', 'assignee'])
            ->whereBetween('starts_at', [now()->startOfWeek(\Carbon\Carbon::SATURDAY), now()->startOfWeek(\Carbon\Carbon::SATURDAY)->addDays(6)->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        // Condensed sales-pipeline view — same PipelineStage/QuoteRequest data as
        // admin.kanban, summarized to stage counts + first few cards per stage.
        $pipelineStages = PipelineStage::where('is_active', true)->orderBy('sort_order')->get();
        $pipelineQuery = $baseScope(QuoteRequest::query())->where('is_archived', false);
        $pipelineLeads = $pipelineQuery->get(['id', 'name', 'car_label', 'current_stage_id']);
        $pipelineByStage = $pipelineStages->map(function ($stage) use ($pipelineLeads) {
            $leads = $pipelineLeads->where('current_stage_id', $stage->id);

            return ['stage' => $stage, 'count' => $leads->count(), 'sample' => $leads->take(3)];
        })->filter(fn ($row) => $row['count'] > 0)->values();

        // "برنامه امروز" (today's schedule) and "پیگیری‌های عقب‌افتاده" (overdue follow-ups) —
        // real data matching 03-sales-dashboard.png's widgets, scoped the same way as
        // everything else on this dashboard. Not the reference's conversion-funnel/today's-
        // schedule-with-icons composition exactly, but genuinely real, not fabricated.
        $todaySchedule = CalendarEvent::query()
            ->forUser($user)
            ->with('quoteRequest')
            ->whereDate('starts_at', today())
            ->where('status', CalendarEvent::STATUS_SCHEDULED)
            ->orderBy('starts_at')
            ->get();

        $overdueFollowUps = $baseScope(QuoteRequest::query())
            ->where('is_archived', false)
            ->whereNotNull('next_call_date')
            ->whereDate('next_call_date', '<', today())
            ->orderBy('next_call_date')
            ->limit(5)
            ->get(['id', 'name', 'car_label', 'next_call_date']);

        return view('admin.dashboard', [
            'pageTitle' => 'داشبورد مدیریت',
            'pageSubtitle' => 'نمای کلی از درخواست‌های استعلام قیمت و محاسبات انجام‌شده روی سایت.',
            'newRequests' => $newRequests,
            'underFollowUp' => $underFollowUp,
            'activeListings' => $activeListings,
            'failedImports' => $failedImports,
            'todayCalcs' => $todayCalcs,
            'todayVin' => $todayVin,
            'unassignedCount' => $unassignedCount,
            'callsToday' => $callsToday,
            'hotLeads' => $hotLeads,
            'weekRequests' => $weekRequests,
            'monthRequests' => $monthRequests,
            'avgTotal' => $avgTotal ?? 0,
            'daily' => $daily,
            'catDist' => $catDist,
            'topCars' => $topCars,
            'recentRequests' => $recentRequests,
            'isAdmin' => $isAdmin,
            'todayRates' => $todayRates,
            'importStatus' => $importStatus,
            'weekEvents' => $weekEvents,
            'pipelineByStage' => $pipelineByStage,
            'todaySchedule' => $todaySchedule,
            'overdueFollowUps' => $overdueFollowUps,
        ]);
    }
}
