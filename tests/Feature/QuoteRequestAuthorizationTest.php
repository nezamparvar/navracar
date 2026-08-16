<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteRequestAuthorizationTest extends TestCase
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

    public function test_admin_can_view_any_lead(): void
    {
        $this->makeStages();
        $admin = $this->makeUser('admin', 'admin-view');
        $salesA = $this->makeUser('sales', 'sales-a-view');

        $leadAssignedToSalesA = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($admin)->get(route('admin.requests.show', $leadAssignedToSalesA))->assertOk();
    }

    public function test_sales_can_view_own_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-own-view');

        $leadAssignedToSalesA = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($salesA)->get(route('admin.requests.show', $leadAssignedToSalesA))->assertOk();
    }

    public function test_sales_cannot_view_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-other-view');
        $salesB = $this->makeUser('sales', 'sales-b-other-view');

        $leadAssignedToSalesB = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
        ]);

        $this->actingAs($salesA)->get(route('admin.requests.show', $leadAssignedToSalesB))->assertForbidden();
    }

    public function test_admin_can_modify_any_lead(): void
    {
        $this->makeStages();
        $admin = $this->makeUser('admin', 'admin-modify');
        $salesA = $this->makeUser('sales', 'sales-a-modify');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($admin)->post(route('admin.requests.temperature', $lead), ['temperature' => 'hot'])->assertRedirect();
        $this->assertSame('hot', $lead->fresh()->temperature);
    }

    public function test_sales_can_modify_own_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-own-modify');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.temperature', $lead), ['temperature' => 'hot'])->assertRedirect();
        $this->assertSame('hot', $lead->fresh()->temperature);
    }

    public function test_sales_cannot_change_temperature_of_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-temp-block');
        $salesB = $this->makeUser('sales', 'sales-b-temp-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'temperature' => 'cold',
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.temperature', $lead), ['temperature' => 'hot'])->assertForbidden();
        $this->assertSame('cold', $lead->fresh()->temperature);
    }

    public function test_sales_cannot_change_status_of_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-status-block');
        $salesB = $this->makeUser('sales', 'sales-b-status-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'follow_up_status' => 'باز',
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.status', $lead), [
            'follow_up_status' => 'بسته - موفق',
        ])->assertForbidden();

        $this->assertSame('باز', $lead->fresh()->follow_up_status);
    }

    public function test_sales_cannot_close_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-close-block');
        $salesB = $this->makeUser('sales', 'sales-b-close-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.close', $lead), [
            'status' => 'بسته - موفق',
        ])->assertForbidden();
    }

    public function test_sales_cannot_archive_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-archive-block');
        $salesB = $this->makeUser('sales', 'sales-b-archive-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'is_archived' => false,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.archive', $lead))->assertForbidden();
        $this->assertFalse($lead->fresh()->is_archived);
    }

    public function test_sales_cannot_unarchive_other_sales_lead(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-unarchive-block');
        $salesB = $this->makeUser('sales', 'sales-b-unarchive-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'is_archived' => true,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.unarchive', $lead))->assertForbidden();
        $this->assertTrue($lead->fresh()->is_archived);
    }

    public function test_sales_cannot_move_other_sales_lead_in_kanban(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-kanban-block');
        $salesB = $this->makeUser('sales', 'sales-b-kanban-block');

        $stage = PipelineStage::first();
        $lead = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
        ]);

        $this->actingAs($salesA)->post(route('admin.kanban.change-stage'), [
            'leadId' => $lead->id,
            'stageId' => $stage->id,
        ])->assertStatus(403);
    }

    public function test_sales_cannot_assign_leads(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-assign-block');
        $salesB = $this->makeUser('sales', 'sales-b-assign-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($salesA)->post(route('admin.requests.assign', $lead), [
            'assigned_to' => $salesB->id,
        ])->assertForbidden();
    }

    public function test_sales_cannot_delete_leads(): void
    {
        $this->makeStages();
        $salesA = $this->makeUser('sales', 'sales-a-delete-block');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($salesA)->delete(route('admin.requests.destroy', $lead))->assertForbidden();
        $this->assertNotNull($lead->fresh());
    }

    public function test_admin_can_delete_leads(): void
    {
        $this->makeStages();
        $admin = $this->makeUser('admin', 'admin-delete');
        $salesA = $this->makeUser('sales', 'sales-a-delete');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead))->assertRedirect();
        $this->assertSoftDeleted($lead);
    }
}
