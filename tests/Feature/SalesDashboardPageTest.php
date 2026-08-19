<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CalendarEvent;
use App\Models\Invoice;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDashboardPageTest extends TestCase
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

    public function test_sales_and_admin_can_open_the_sales_dashboard(): void
    {
        $sales = $this->makeUser('sales', 'sales-dash-1');
        $admin = $this->makeUser('admin', 'admin-dash-1');

        $this->actingAs($sales)->get(route('admin.sales-dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.sales-dashboard'))->assertOk();
    }

    public function test_content_manager_cannot_open_the_sales_dashboard(): void
    {
        $content = $this->makeUser('content_manager', 'content-dash-1');

        $this->actingAs($content)->get(route('admin.sales-dashboard'))->assertForbidden();
    }

    public function test_sales_dashboard_scopes_widgets_to_the_signed_in_rep(): void
    {
        $salesA = $this->makeUser('sales', 'sales-dash-a');
        $salesB = $this->makeUser('sales', 'sales-dash-b');

        $newLeadStage = PipelineStage::create(['name' => 'سرنخ جدید', 'slug' => 'new-lead', 'sort_order' => 1, 'is_active' => true]);
        PipelineStage::create(['name' => 'از دست رفته', 'slug' => 'lost', 'sort_order' => 99, 'is_active' => true]);

        QuoteRequest::create([
            'name' => 'Lead A', 'phone' => '0910', 'assigned_to' => $salesA->id,
            'current_stage_id' => $newLeadStage->id, 'is_archived' => false, 'created_at' => now(),
        ]);
        QuoteRequest::create([
            'name' => 'Lead B', 'phone' => '0911', 'assigned_to' => $salesB->id,
            'current_stage_id' => $newLeadStage->id, 'is_archived' => false, 'created_at' => now(),
        ]);

        $responseA = $this->actingAs($salesA)->get(route('admin.sales-dashboard'));
        $responseA->assertOk()->assertViewHas('newLeads', 1);

        $responseB = $this->actingAs($salesB)->get(route('admin.sales-dashboard'));
        $responseB->assertOk()->assertViewHas('newLeads', 1);
    }

    public function test_sales_dashboard_counts_today_meetings_and_open_proforma(): void
    {
        $sales = $this->makeUser('sales', 'sales-dash-meetings');

        $lead = QuoteRequest::create([
            'name' => 'Lead', 'phone' => '0912', 'assigned_to' => $sales->id, 'created_at' => now(),
        ]);

        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_CONSULTATION_MEETING,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->setTime(14, 0),
            'ends_at' => now()->setTime(14, 30),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);
        // A follow-up call today should not count as a "meeting".
        CalendarEvent::create([
            'type' => CalendarEvent::TYPE_FOLLOW_UP_CALL,
            'assigned_to' => $sales->id,
            'created_by' => $sales->id,
            'starts_at' => now()->setTime(9, 0),
            'ends_at' => now()->setTime(9, 15),
            'status' => CalendarEvent::STATUS_SCHEDULED,
        ]);

        Invoice::create([
            'request_id' => $lead->id, 'invoice_number' => 'INV-1', 'customer_name' => 'Lead',
            'customer_phone' => '0912', 'car_label' => 'Test Car', 'breakdown_json' => '[]',
            'total_amount' => 1000, 'currency' => 'aed', 'status' => 'ارسال‌شده',
        ]);
        Invoice::create([
            'request_id' => $lead->id, 'invoice_number' => 'INV-2', 'customer_name' => 'Lead',
            'customer_phone' => '0912', 'car_label' => 'Test Car', 'breakdown_json' => '[]',
            'total_amount' => 2000, 'currency' => 'aed', 'status' => 'تایید شده',
        ]);

        $response = $this->actingAs($sales)->get(route('admin.sales-dashboard'));
        $response->assertOk()
            ->assertViewHas('todayMeetings', 1)
            ->assertViewHas('openProforma', 1);
    }

    public function test_sales_dashboard_funnel_reflects_real_pipeline_stages_only(): void
    {
        $sales = $this->makeUser('sales', 'sales-dash-funnel');

        $stage1 = PipelineStage::create(['name' => 'سرنخ جدید', 'slug' => 'new-lead', 'sort_order' => 1, 'is_active' => true]);
        PipelineStage::create(['name' => 'از دست رفته', 'slug' => 'lost', 'sort_order' => 99, 'is_active' => true]);

        QuoteRequest::create([
            'name' => 'Lead A', 'phone' => '0913', 'assigned_to' => $sales->id,
            'current_stage_id' => $stage1->id, 'is_archived' => false, 'created_at' => now(),
        ]);

        $response = $this->actingAs($sales)->get(route('admin.sales-dashboard'));
        $response->assertOk();

        $funnel = $response->viewData('funnel');
        $this->assertCount(1, $funnel);
        $this->assertSame('new-lead', $funnel[0]['stage']->slug);
        $this->assertSame(1, $funnel[0]['count']);
    }
}
