<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveBehaviorTest extends TestCase
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

    public function test_archived_leads_hidden_from_normal_list_by_default(): void
    {
        $admin = $this->makeUser('admin', 'admin-archive-visibility');

        $activeLead = QuoteRequest::create([
            'name' => 'Active Lead',
            'phone' => '0910',
            'assigned_to' => $admin->id,
            'is_archived' => false,
        ]);

        $archivedLead = QuoteRequest::create([
            'name' => 'Archived Lead',
            'phone' => '0911',
            'assigned_to' => $admin->id,
            'is_archived' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index'));
        $response->assertSee('Active Lead')->assertDontSee('Archived Lead');
    }

    public function test_show_archived_checkbox_displays_archived_leads(): void
    {
        $admin = $this->makeUser('admin', 'admin-show-archived');

        $archivedLead = QuoteRequest::create([
            'name' => 'Archived Lead',
            'phone' => '0911',
            'assigned_to' => $admin->id,
            'is_archived' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index', ['show_archived' => 1]));
        $response->assertSee('Archived Lead');
    }

    public function test_archive_preserves_all_crm_data(): void
    {
        $sales = $this->makeUser('sales', 'sales-archive-data');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'email' => 'test@example.com',
            'car_label' => 'Toyota Camry',
            'category' => 'personal',
            'temperature' => 'hot',
            'notes' => 'Important lead',
            'assigned_to' => $sales->id,
            'follow_up_status' => 'باز',
            'total_with_profit' => 5000000,
        ]);

        $this->actingAs($sales)->post(route('admin.requests.archive', $lead))->assertRedirect();

        $lead->refresh();
        $this->assertTrue($lead->is_archived);
        $this->assertSame('Lead', $lead->name);
        $this->assertSame('0910', $lead->phone);
        $this->assertSame('test@example.com', $lead->email);
        $this->assertSame('Toyota Camry', $lead->car_label);
        $this->assertSame('personal', $lead->category);
        $this->assertSame('hot', $lead->temperature);
        $this->assertSame('Important lead', $lead->notes);
        $this->assertSame($sales->id, $lead->assigned_to);
        $this->assertSame('باز', $lead->follow_up_status);
        $this->assertSame(5000000, $lead->total_with_profit);
    }

    public function test_unarchive_restores_lead_to_normal_list(): void
    {
        $sales = $this->makeUser('sales', 'sales-unarchive');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
            'is_archived' => true,
        ]);

        $this->actingAs($sales)->post(route('admin.requests.unarchive', $lead))->assertRedirect();

        $lead->refresh();
        $this->assertFalse($lead->is_archived);

        $response = $this->actingAs($sales)->get(route('admin.requests.index'));
        $response->assertSee('Lead');
    }

    public function test_archived_leads_excluded_from_kanban(): void
    {
        $admin = $this->makeUser('admin', 'admin-kanban-archive');
        $stage = PipelineStage::create(['name' => 'جدید', 'slug' => 'new', 'sort_order' => 1, 'is_active' => true]);

        $activeLead = QuoteRequest::create([
            'name' => 'Active Lead',
            'phone' => '0910',
            'assigned_to' => $admin->id,
            'is_archived' => false,
            'current_stage_id' => $stage->id,
        ]);

        $archivedLead = QuoteRequest::create([
            'name' => 'Archived Lead',
            'phone' => '0911',
            'assigned_to' => $admin->id,
            'is_archived' => true,
            'current_stage_id' => $stage->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.kanban'));
        $response->assertSee('Active Lead')->assertDontSee('Archived Lead');
    }

    public function test_only_authorized_users_can_archive(): void
    {
        $salesA = $this->makeUser('sales', 'sales-a-archive-auth');
        $salesB = $this->makeUser('sales', 'sales-b-archive-auth');

        $leadOfA = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
            'is_archived' => false,
        ]);

        $leadOfB = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'is_archived' => false,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.archive', $leadOfA))->assertRedirect();
        $this->assertTrue($leadOfA->fresh()->is_archived);

        $this->actingAs($salesA)->post(route('admin.requests.archive', $leadOfB))->assertForbidden();
        $this->assertFalse($leadOfB->fresh()->is_archived);
    }

    public function test_archive_and_unarchive_independent_from_soft_delete(): void
    {
        $admin = $this->makeUser('admin', 'admin-archive-soft-delete');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('admin.requests.archive', $lead))->assertRedirect();
        $this->assertTrue($lead->fresh()->is_archived);
        $this->assertNull($lead->fresh()->deleted_at);

        $this->actingAs($admin)->post(route('admin.requests.unarchive', $lead))->assertRedirect();
        $this->assertFalse($lead->fresh()->is_archived);
        $this->assertNull($lead->fresh()->deleted_at);
    }

    public function test_archiving_creates_activity_log(): void
    {
        $sales = $this->makeUser('sales', 'sales-archive-log');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $this->actingAs($sales)->post(route('admin.requests.archive', $lead));

        $activity = LeadActivity::where('request_id', $lead->id)->latest()->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('بایگانی', $activity->note);
    }
}
