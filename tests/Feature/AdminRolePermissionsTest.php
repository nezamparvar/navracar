<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolePermissionsTest extends TestCase
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

    public function test_content_manager_is_blocked_from_sales_and_admin_only_sections(): void
    {
        $user = $this->makeUser('content_manager', 'cm-scope-test');

        $this->actingAs($user)->get(route('admin.car-listings.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.posts.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.home-slides.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.menu-items.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.content-dashboard'))->assertOk();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.kanban'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.calculations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.vin-checks.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.activity-log.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.templates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_sales_is_blocked_from_content_and_admin_only_sections(): void
    {
        $user = $this->makeUser('sales', 'sales-scope-test');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.kanban'))->assertOk();
        $this->actingAs($user)->get(route('admin.requests.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.invoices.index'))->assertOk();

        $this->actingAs($user)->get(route('admin.car-listings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.posts.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.home-slides.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.menu-items.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.content-dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.calculations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.vin-checks.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.activity-log.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.templates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_sales_only_sees_and_edits_invoices_assigned_to_themselves(): void
    {
        $salesA = $this->makeUser('sales', 'sales-a');
        $salesB = $this->makeUser('sales', 'sales-b');

        $leadForA = QuoteRequest::create(['name' => 'Lead A', 'phone' => '0910', 'assigned_to' => $salesA->id]);

        $invoiceOwnedByA = Invoice::create([
            'customer_name' => 'مشتری آ', 'customer_phone' => '0910', 'total_amount' => 1000,
            'currency' => 'toman', 'invoice_type' => 'full', 'status' => 'پیش‌نویس',
            'created_by' => $salesA->id, 'request_id' => $leadForA->id, 'invoice_number' => 'NVK-TEST-A',
        ]);

        $invoiceOwnedByB = Invoice::create([
            'customer_name' => 'مشتری ب', 'customer_phone' => '0911', 'total_amount' => 2000,
            'currency' => 'toman', 'invoice_type' => 'full', 'status' => 'پیش‌نویس',
            'created_by' => $salesB->id, 'invoice_number' => 'NVK-TEST-B',
        ]);

        $this->actingAs($salesA)->get(route('admin.invoices.show', $invoiceOwnedByA))->assertOk();
        $this->actingAs($salesA)->get(route('admin.invoices.show', $invoiceOwnedByB))->assertForbidden();
        $this->actingAs($salesA)->get(route('admin.invoices.create', ['id' => $invoiceOwnedByB->id]))->assertForbidden();
        $this->actingAs($salesA)->post(route('admin.invoices.status', $invoiceOwnedByB), ['status' => 'ارسال‌شده'])->assertForbidden();

        $indexAsA = $this->actingAs($salesA)->get(route('admin.invoices.index'));
        $indexAsA->assertOk()->assertSee('NVK-TEST-A')->assertDontSee('NVK-TEST-B');
    }

    public function test_creating_a_standalone_invoice_not_linked_to_a_request_succeeds(): void
    {
        // این سناریو دقیقاً همان باگی است که خطای 500 می‌داد: صدور پیش‌فاکتور
        // جدید بدون اتصال به یک درخواست موجود (request_id خالی).
        $sales = $this->makeUser('sales', 'sales-standalone');

        $response = $this->actingAs($sales)->post(route('admin.invoices.store'), [
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09120000000',
            'pricing_mode' => 'manual',
            'adjustment_reason' => 'پیش‌فاکتور مستقل خدمات دستی',
            'total_amount' => '15,000,000',
            'currency' => 'toman',
            'invoice_type' => 'full',
            'request_id' => '0',
            'b_label' => ['قیمت خودرو'],
            'b_rate' => [''],
            'b_amount' => ['15000000'],
        ]);

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertNull($invoice->request_id);
        $this->assertSame($sales->id, $invoice->created_by);
    }

    public function test_login_redirects_each_role_to_its_own_home_page(): void
    {
        $admin = $this->makeUser('admin', 'admin-redirect-test');
        $sales = $this->makeUser('sales', 'sales-redirect-test');
        $cm = $this->makeUser('content_manager', 'cm-redirect-test');

        $this->post(route('login'), ['username' => $admin->username, 'password' => 'secret'])
            ->assertRedirect(route('admin.dashboard'));
        auth()->logout();

        $this->post(route('login'), ['username' => $sales->username, 'password' => 'secret'])
            ->assertRedirect(route('admin.dashboard'));
        auth()->logout();

        $this->post(route('login'), ['username' => $cm->username, 'password' => 'secret'])
            ->assertRedirect(route('admin.content-dashboard'));
    }
}
