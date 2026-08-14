<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use App\Models\VinCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $todayRequests = QuoteRequest::whereDate('created_at', today())->count();
        $todayCalcs = CalculationLog::whereDate('created_at', today())->count();
        $todayVin = VinCheck::whereDate('created_at', today())->count();
        $unassignedCount = $isAdmin ? QuoteRequest::whereNull('assigned_to')->count() : 0;

        $activeStageIds = PipelineStage::whereIn('slug', ['delivered', 'lost'])->pluck('id');

        $baseScope = fn ($query) => $isAdmin ? $query : $query->where('assigned_to', $user->id);

        $callsToday = $baseScope(QuoteRequest::query())->whereDate('next_call_date', today())->count();
        $hotLeads = $baseScope(QuoteRequest::query())
            ->where('temperature', 'hot')
            ->where(function ($q) use ($activeStageIds) {
                $q->whereNull('current_stage_id')->orWhereNotIn('current_stage_id', $activeStageIds);
            })
            ->count();

        $weekRequests = QuoteRequest::where('created_at', '>=', now()->subDays(7)->startOfDay())->count();
        $monthRequests = QuoteRequest::where('created_at', '>=', now()->subDays(30)->startOfDay())->count();
        $avgTotal = QuoteRequest::where('total_with_profit', '>', 0)->avg('total_with_profit');

        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $daily[$d] = ['date' => $d, 'requests' => 0, 'calcs' => 0];
        }
        QuoteRequest::selectRaw('DATE(created_at) d, COUNT(*) c')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->get()
            ->each(function ($r) use (&$daily) {
                if (isset($daily[$r->d])) {
                    $daily[$r->d]['requests'] = (int) $r->c;
                }
            });
        CalculationLog::selectRaw('DATE(created_at) d, COUNT(*) c')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->get()
            ->each(function ($r) use (&$daily) {
                if (isset($daily[$r->d])) {
                    $daily[$r->d]['calcs'] = (int) $r->c;
                }
            });

        $catDist = CalculationLog::select('category', DB::raw('COUNT(*) c'))
            ->whereNotNull('category')->where('category', '!=', '')
            ->groupBy('category')->orderByDesc('c')->get();

        $topCars = CalculationLog::select('car_label', DB::raw('COUNT(*) c'))
            ->whereNotNull('car_label')->whereNotIn('car_label', ['', 'مشخص نشده'])
            ->groupBy('car_label')->orderByDesc('c')->limit(8)->get();

        $recentRequests = $baseScope(QuoteRequest::query())
            ->latest('created_at')->limit(8)
            ->get(['id', 'created_at', 'name', 'phone', 'car_label', 'total_with_profit', 'email_sent']);

        return view('admin.dashboard', [
            'pageTitle' => 'داشبورد مدیریت',
            'pageSubtitle' => 'نمای کلی از درخواست‌های استعلام قیمت و محاسبات انجام‌شده روی سایت.',
            'todayRequests' => $todayRequests,
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
        ]);
    }
}
