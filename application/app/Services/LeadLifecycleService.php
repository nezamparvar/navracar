<?php

namespace App\Services;

use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;

class LeadLifecycleService
{
    public function closeSuccessfully(QuoteRequest $lead, int $userId, ?string $note = null): void
    {
        $lead->update(['follow_up_status' => 'بسته - موفق']);

        $activityNote = 'درخواست بسته شد — بسته - موفق';
        if ($note) {
            $activityNote .= ' — '.$note;
        }

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $userId,
            'activity_type' => 'status_change',
            'note' => $activityNote,
        ]);
    }

    public function closeUnsuccessfully(QuoteRequest $lead, int $userId, ?string $note = null, ?string $lossReason = null): void
    {
        $lead->update([
            'follow_up_status' => 'بسته - ناموفق',
        ]);

        $activityNote = 'درخواست بسته شد — بسته - ناموفق';
        if ($note) {
            $activityNote .= ' — '.$note;
        }

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $userId,
            'activity_type' => 'status_change',
            'note' => $activityNote,
        ]);

        $lostStage = PipelineStage::where('slug', 'lost')->first();
        if ($lostStage) {
            $lead->update([
                'current_stage_id' => $lostStage->id,
                'loss_reason' => $lossReason ?? 'درخواست بسته شد',
            ]);
        }
    }

    public function updateStatus(QuoteRequest $lead, string $newStatus, int $userId, ?string $note = null): void
    {
        if ($newStatus === 'بسته - موفق') {
            $this->closeSuccessfully($lead, $userId, $note);
        } elseif ($newStatus === 'بسته - ناموفق') {
            $this->closeUnsuccessfully($lead, $userId, $note);
        } else {
            $lead->update(['follow_up_status' => $newStatus]);
            LeadActivity::create([
                'request_id' => $lead->id,
                'admin_user_id' => $userId,
                'activity_type' => 'status_change',
                'note' => 'تغییر وضعیت به «'.$newStatus.'»'.($note ? ' — '.$note : ''),
            ]);
        }
    }

    public function archive(QuoteRequest $lead, int $userId): void
    {
        $lead->update(['is_archived' => true]);

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $userId,
            'activity_type' => 'note',
            'note' => 'درخواست بایگانی شد',
        ]);
    }

    public function unarchive(QuoteRequest $lead, int $userId): void
    {
        $lead->update(['is_archived' => false]);

        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $userId,
            'activity_type' => 'note',
            'note' => 'درخواست از بایگانی خارج شد',
        ]);
    }
}
