<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\LeadActivity;
use App\Models\LossReason;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $stages = PipelineStage::where('is_active', true)->orderBy('sort_order')->get();

        $query = QuoteRequest::query()->with('assignee')->where('is_archived', false);

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        } elseif ($request->filled('sales') && (string) $request->string('sales') !== 'all') {
            $query->where('assigned_to', (int) $request->input('sales'));
        }
        if ($temp = (string) $request->string('temp', '')) {
            $query->where('temperature', $temp);
        }
        if ($source = (string) $request->string('source', '')) {
            $query->where('source', $source);
        }
        if ($q = (string) $request->string('q', '')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $allLeads = $query->orderByDesc('created_at')->get();
        $leadsByStage = $stages->mapWithKeys(fn ($s) => [$s->id => collect()]);
        foreach ($allLeads as $lead) {
            $sid = $lead->current_stage_id ?: $stages->first()?->id;
            if ($leadsByStage->has($sid)) {
                $leadsByStage[$sid]->push($lead);
            }
        }

        return view('admin.kanban', [
            'pageTitle' => 'پایپ‌لاین فروش (کانبان)',
            'stages' => $stages,
            'leadsByStage' => $leadsByStage,
            'staffList' => $user->isAdmin() ? AdminUser::orderBy('username')->get() : collect(),
            'lossReasons' => LossReason::where('is_active', true)->get(),
            'sources' => QuoteRequest::whereNotNull('source')->where('source', '!=', '')->distinct()->pluck('source'),
            'filters' => $request->only(['temp', 'source', 'q', 'sales']),
        ]);
    }

    public function updateStage(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'leadId' => ['required', 'integer'],
            'stageId' => ['required', 'integer'],
            'lossReason' => ['nullable', 'string'],
        ]);

        $lead = QuoteRequest::find($data['leadId']);
        if (! $lead) {
            return response()->json(['success' => false, 'message' => 'سرنخ یافت نشد.'], 404);
        }
        $this->authorize('updateStatus', $lead);

        $stage = PipelineStage::find($data['stageId']);
        if (! $stage) {
            return response()->json(['success' => false, 'message' => 'مرحله نامعتبر است.'], 422);
        }

        $lossReason = trim($data['lossReason'] ?? '');
        if ($stage->slug === 'lost' && $lossReason === '') {
            return response()->json(['success' => false, 'message' => 'برای انتقال به «از دست رفته» انتخاب دلیل الزامی است.', 'needsLossReason' => true], 422);
        }

        $lead->update([
            'current_stage_id' => $stage->id,
            'loss_reason' => $stage->slug === 'lost' ? $lossReason : null,
        ]);

        $note = 'تغییر مرحله به «'.$stage->name.'»'.($lossReason ? ' — دلیل: '.$lossReason : '');
        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $user->id,
            'activity_type' => 'status_change',
            'note' => $note,
        ]);

        ActivityLogger::info('تغییر مرحله پایپ‌لاین', ['lead' => $lead->id, 'stage' => $stage->slug]);

        return response()->json(['success' => true]);
    }

    public function updateStageName(Request $request, PipelineStage $stage)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $oldName = $stage->name;
        $stage->update(['name' => $data['name']]);

        ActivityLogger::info('تغییر نام مرحله پایپ‌لاین', ['stage_id' => $stage->id, 'old_name' => $oldName, 'new_name' => $data['name']]);

        return response()->json(['success' => true, 'message' => 'نام مرحله به‌روزرسانی شد.']);
    }
}
