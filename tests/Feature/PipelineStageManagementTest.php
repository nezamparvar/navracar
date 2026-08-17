<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageManagementTest extends TestCase
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

    public function test_request_list_uses_pipeline_sort_order_column(): void
    {
        $admin = $this->makeUser('admin', 'admin-request-list');
        PipelineStage::create(['name' => 'Second', 'slug' => 'second', 'sort_order' => 2]);
        PipelineStage::create(['name' => 'First', 'slug' => 'first', 'sort_order' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['First', 'Second']);
    }

    public function test_admin_can_add_and_delete_an_empty_pipeline_column(): void
    {
        $admin = $this->makeUser('admin', 'admin-pipeline-manage');
        PipelineStage::create(['name' => 'Existing', 'slug' => 'existing', 'sort_order' => 1]);

        $this->actingAs($admin)->post(route('admin.pipeline-stages.store'), [
            'name' => 'در انتظار مدارک',
        ])->assertRedirect(route('admin.kanban'));

        $stage = PipelineStage::where('name', 'در انتظار مدارک')->firstOrFail();
        $this->assertSame(2, (int) $stage->sort_order);
        $this->assertStringStartsWith('custom-', $stage->slug);

        $this->actingAs($admin)->delete(route('admin.pipeline-stages.destroy', $stage))
            ->assertRedirect(route('admin.kanban'));

        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_pipeline_column_with_requests_cannot_be_deleted(): void
    {
        $admin = $this->makeUser('admin', 'admin-pipeline-protected');
        $stage = PipelineStage::create(['name' => 'Occupied', 'slug' => 'occupied', 'sort_order' => 1]);
        PipelineStage::create(['name' => 'Other', 'slug' => 'other', 'sort_order' => 2]);
        QuoteRequest::create([
            'name' => 'Lead in stage',
            'phone' => '09120000000',
            'current_stage_id' => $stage->id,
        ]);

        $this->actingAs($admin)->from(route('admin.kanban'))
            ->delete(route('admin.pipeline-stages.destroy', $stage))
            ->assertRedirect(route('admin.kanban'))
            ->assertSessionHasErrors('stage');

        $this->assertDatabaseHas('pipeline_stages', ['id' => $stage->id]);
    }

    public function test_sales_cannot_add_or_delete_pipeline_columns(): void
    {
        $sales = $this->makeUser('sales', 'sales-pipeline-manage');
        $stage = PipelineStage::create(['name' => 'Existing', 'slug' => 'existing', 'sort_order' => 1]);

        $this->actingAs($sales)->post(route('admin.pipeline-stages.store'), ['name' => 'Unauthorized'])
            ->assertForbidden();
        $this->actingAs($sales)->delete(route('admin.pipeline-stages.destroy', $stage))
            ->assertForbidden();
    }
}
