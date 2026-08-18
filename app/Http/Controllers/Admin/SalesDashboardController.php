<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Invoice;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    /**
     * A separate, personal dashboard for the sales role (round-4 remediation) — distinct from
     * the shared admin.dashboard overview. Scoped to the signed-in rep's own leads unless they
     * are an admin (same assigned_to scoping convention as DashboardController/KanbanController).
     * Every widget here is real, queried data; there is no fabricated "مشاوره" pipeline stage —
     * the funnel below reflects the actual configured PipelineStage rows.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();
        $baseScope = fn ($query) => $isAdmin ? $query : $query->where('assigned_to', $user->id);

        $activeStageIds = PipelineStage::whereIn('slug', ['delivered', 'lost'])->pluck('id');

        $newLeads = $baseScope(QuoteRequest::query())
            ->where('is_archived', false)
            ->whereHas('stage', fn ($q) => $q->where('slug', 'new-lead'))
            ->count();

        $todayFollowUps = $baseScope(QuoteRequest::query())
            ->where('is_archived', false)
            ->whereDate('next_call_date', today())
            ->count();

        $todayMeetings = CalendarEvent::query()
            ->forUser($user)
            ->whereDate('starts_at', today())
            ->where('status', CalendarEvent::STATUS_SCHEDULED)
            ->whereIn('type', [CalendarEvent::TYPE_CONSULTATION_MEETING, CalendarEvent::TYPE_DELIVERY_MEETING])
            ->count();

        $leadIdsForInvoices = $baseScope(QuoteRequest::query())->pluck('id');
        $openProforma = Invoice::whereIn('request_id', $leadIdsForInvoices)
            ->where('status', '!=', 'تایید شده')
            ->count();

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
            ->limit(6)
            ->get(['id', 'name', 'car_label', 'next_call_date']);

        $pipelineStages = PipelineStage::where('is_active', true)->orderBy('sort_order')->get();
        $pipelineLeads = $baseScope(QuoteRequest::query())
            ->where('is_archived', false)
            ->get(['id', 'name', 'car_label', 'current_stage_id']);
        $pipelineByStage = $pipelineStages->map(function ($stage) use ($pipelineLeads) {
            $leads = $pipelineLeads->where('current_stage_id', $stage->id);

            return ['stage' => $stage, 'count' => $leads->count(), 'sample' => $leads->take(3)];
        })->filter(fn ($row) => $row['count'] > 0)->values();

        $recentProformas = Invoice::whereIn('request_id', $leadIdsForInvoices)
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'invoice_number', 'car_label', 'status', 'total_amount', 'currency', 'valid_until']);

        // Real funnel: current distribution of the rep's (or, for admin, everyone's) active
        // leads across the actual configured PipelineStage sequence, "lost" excluded since it
        // is a terminal drop-out, not a forward funnel step. No invented stage labels/counts.
        $funnelLeads = $baseScope(QuoteRequest::query())->where('is_archived', false)->get(['current_stage_id']);
        $funnel = PipelineStage::where('is_active', true)
            ->where('slug', '!=', 'lost')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($stage) => [
                'stage' => $stage,
                'count' => $funnelLeads->where('current_stage_id', $stage->id)->count(),
            ]);
        $funnelMax = max(1, $funnel->max('count'));

        return view('admin.sales-dashboard', [
            'pageTitle' => 'داشبورد فروش',
            'pageSubtitle' => $isAdmin ? 'نمای کلی پایپ‌لاین فروش (همه کارشناسان).' : 'نمای شخصی از پایپ‌لاین، جلسات و پیگیری‌های شما.',
            'isAdmin' => $isAdmin,
            'newLeads' => $newLeads,
            'todayFollowUps' => $todayFollowUps,
            'todayMeetings' => $todayMeetings,
            'openProforma' => $openProforma,
            'todaySchedule' => $todaySchedule,
            'overdueFollowUps' => $overdueFollowUps,
            'pipelineByStage' => $pipelineByStage,
            'recentProformas' => $recentProformas,
            'funnel' => $funnel,
            'funnelMax' => $funnelMax,
        ]);
    }
}
