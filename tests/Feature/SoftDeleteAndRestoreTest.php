<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteAndRestoreTest extends TestCase
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

    public function test_admin_can_soft_delete_quote_request(): void
    {
        $admin = $this->makeUser('admin', 'admin-soft-delete');
        $sales = $this->makeUser('sales', 'sales-soft-delete');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead))
            ->assertRedirect(route('admin.requests.index'));

        $this->assertSoftDeleted($lead);
        $this->assertNull(QuoteRequest::find($lead->id));
        $this->assertNotNull(QuoteRequest::withTrashed()->find($lead->id));
    }

    public function test_sales_cannot_delete_quote_request(): void
    {
        $sales = $this->makeUser('sales', 'sales-cannot-delete');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $this->actingAs($sales)->delete(route('admin.requests.destroy', $lead))->assertForbidden();
        $this->assertNotSoftDeleted($lead);
    }

    public function test_deleted_request_is_hidden_from_normal_list(): void
    {
        $admin = $this->makeUser('admin', 'admin-see-deleted');
        $sales = $this->makeUser('sales', 'sales-see-deleted');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead));

        $indexResponse = $this->actingAs($admin)->get(route('admin.requests.index'));
        $indexResponse->assertDontSee($lead->name);

        $indexResponse = $this->actingAs($sales)->get(route('admin.requests.index'));
        $indexResponse->assertDontSee($lead->name);
    }

    public function test_admin_can_view_deleted_requests(): void
    {
        $admin = $this->makeUser('admin', 'admin-view-deleted');
        $sales = $this->makeUser('sales', 'sales-view-deleted');

        $lead = QuoteRequest::create([
            'name' => 'Deleted Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead));

        $response = $this->actingAs($admin)->get(route('admin.requests.deleted.index'));
        $response->assertOk()->assertSee('Deleted Lead');
    }

    public function test_sales_cannot_view_deleted_requests(): void
    {
        $sales = $this->makeUser('sales', 'sales-view-deleted-forbidden');

        $this->actingAs($sales)->get(route('admin.requests.deleted.index'))->assertForbidden();
    }

    public function test_admin_can_restore_deleted_request(): void
    {
        $admin = $this->makeUser('admin', 'admin-restore');
        $sales = $this->makeUser('sales', 'sales-restore');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $leadId = $lead->id;
        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead));
        $this->assertSoftDeleted($lead);

        $deletedLead = QuoteRequest::withTrashed()->find($leadId);
        $this->actingAs($admin)->post(route('admin.requests.restore', $deletedLead))->assertRedirect();

        $lead->refresh();
        $this->assertNotSoftDeleted($lead);
    }

    public function test_soft_deleted_request_is_not_resolved_by_normal_crm_routes(): void
    {
        $admin = $this->makeUser('admin', 'admin-deleted-route-boundary');
        $lead = QuoteRequest::create([
            'name' => 'Deleted route boundary',
            'phone' => '09120000001',
            'assigned_to' => $admin->id,
        ]);

        $lead->delete();

        $this->actingAs($admin)->get(route('admin.requests.show', $lead->id))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.requests.archive', $lead->id))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.requests.restore', $lead->id))->assertRedirect();
        $this->assertNotNull(QuoteRequest::find($lead->id));
    }

    public function test_admin_can_force_delete_deleted_request(): void
    {
        $admin = $this->makeUser('admin', 'admin-force-delete');
        $sales = $this->makeUser('sales', 'sales-force-delete');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $leadId = $lead->id;
        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead));
        $this->assertSoftDeleted($lead);

        $deletedLead = QuoteRequest::withTrashed()->find($leadId);
        $this->actingAs($admin)->delete(route('admin.requests.force-delete', $deletedLead))->assertRedirect();

        $this->assertNull(QuoteRequest::withTrashed()->find($leadId));
    }

    public function test_sales_cannot_restore_or_force_delete(): void
    {
        $admin = $this->makeUser('admin', 'admin-delete-sales');
        $sales = $this->makeUser('sales', 'sales-cannot-restore');

        $lead = QuoteRequest::create([
            'name' => 'Lead',
            'phone' => '0910',
            'assigned_to' => $sales->id,
        ]);

        $leadId = $lead->id;
        $this->actingAs($admin)->delete(route('admin.requests.destroy', $lead));

        $deletedLead = QuoteRequest::withTrashed()->find($leadId);

        $this->actingAs($sales)->post(route('admin.requests.restore', $deletedLead))->assertForbidden();
        $this->actingAs($sales)->delete(route('admin.requests.force-delete', $deletedLead))->assertForbidden();

        $this->assertSoftDeleted($lead);
    }
}
