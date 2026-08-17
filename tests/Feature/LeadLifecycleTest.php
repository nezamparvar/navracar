<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $username): AdminUser
    {
        return AdminUser::create([
            'username' => $username,
            'password_hash' => bcrypt('secret'),
            'full_name' => ucfirst($role).' Test',
            'role' => $role,
        ]);
    }

    private function makeStages()
    {
        PipelineStage::create(['name' => 'نو', 'slug' => 'new', 'sort_order' => 1, 'is_active' => true]);
        PipelineStage::create(['name' => 'از دست رفته', 'slug' => 'lost', 'sort_order' => 10, 'is_active' => true]);
    }

    public function test_closing_lead_as_successful_updates_status_only(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-successful-close');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
            'follow_up_status' => 'باز',
        ]);

        $this->actingAs($user)->post(route('admin.requests.close', $lead), [
            'status' => 'بسته - موفق',
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame('بسته - موفق', $lead->follow_up_status);
        $this->assertNull($lead->current_stage_id);
        $this->assertNull($lead->loss_reason);

        $activities = $lead->activities()->get();
        $this->assertCount(1, $activities);
        $this->assertStringContainsString('بسته - موفق', $activities[0]->note);
    }

    public function test_closing_lead_as_unsuccessful_moves_to_lost_stage(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-unsuccessful-close');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
            'follow_up_status' => 'باز',
        ]);

        $this->actingAs($user)->post(route('admin.requests.close', $lead), [
            'status' => 'بسته - ناموفق',
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame('بسته - ناموفق', $lead->follow_up_status);

        $lostStage = PipelineStage::where('slug', 'lost')->first();
        $this->assertSame($lostStage->id, $lead->current_stage_id);
        $this->assertNotNull($lead->loss_reason);
    }

    public function test_closing_from_status_form_as_unsuccessful_moves_to_lost(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-status-form-close');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
            'follow_up_status' => 'باز',
        ]);

        $this->actingAs($user)->post(route('admin.requests.status', $lead), [
            'follow_up_status' => 'بسته - ناموفق',
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame('بسته - ناموفق', $lead->follow_up_status);

        $lostStage = PipelineStage::where('slug', 'lost')->first();
        $this->assertSame($lostStage->id, $lead->current_stage_id);
    }

    public function test_lead_lifecycle_changes_are_logged(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-log-activities');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
            'follow_up_status' => 'باز',
        ]);

        $this->actingAs($user)->post(route('admin.requests.close', $lead), [
            'status' => 'بسته - ناموفق',
        ]);

        $activities = $lead->activities()->get();
        $this->assertCount(1, $activities);
        $this->assertSame('status_change', $activities[0]->activity_type);
        $this->assertStringContainsString('بسته - ناموفق', $activities[0]->note);
    }

    public function test_archiving_creates_activity_log(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-archive-log');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)->post(route('admin.requests.archive', $lead))->assertRedirect();

        $lead->refresh();
        $this->assertTrue($lead->is_archived);

        $activities = $lead->activities()->get();
        $this->assertCount(1, $activities);
        $this->assertStringContainsString('بایگانی شد', $activities[0]->note);
    }

    public function test_unarchiving_creates_activity_log(): void
    {
        $this->makeStages();
        $user = $this->makeUser('sales', 'sales-unarchive-log');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $user->id,
            'is_archived' => true,
        ]);

        $this->actingAs($user)->post(route('admin.requests.unarchive', $lead))->assertRedirect();

        $lead->refresh();
        $this->assertFalse($lead->is_archived);

        $activities = $lead->activities()->get();
        $this->assertCount(1, $activities);
        $this->assertStringContainsString('خارج شد', $activities[0]->note);
    }
}
