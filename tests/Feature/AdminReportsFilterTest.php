<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CalculationLog;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use App\Models\VinCheck;
use App\Support\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for a real bug found via manual simulation: several admin
 * list/report/export endpoints used `if ($x = $request->string('key', ''))`,
 * but Request::string() always returns a truthy Stringable object even when
 * the query param is absent, so the "empty filter" branch was never taken —
 * these pages silently showed zero rows (or exported the wrong dataset) by
 * default, with existing data in the database.
 */
class AdminReportsFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'admin-test',
            'password_hash' => bcrypt('secret'),
            'full_name' => 'Test Admin',
            'role' => 'admin',
        ]);
    }

    public function test_calculations_report_shows_existing_rows_without_filters(): void
    {
        CalculationLog::create(['car_label' => 'Mercedes-Benz E200', 'category' => 'c2000', 'total_with_profit' => 950000000]);

        $this->actingAs($this->admin())
            ->get(route('admin.calculations.index'))
            ->assertOk()
            ->assertSee('Mercedes-Benz E200');
    }

    public function test_requests_report_shows_existing_rows_without_filters(): void
    {
        QuoteRequest::create(['name' => 'علی رضایی', 'phone' => '0912', 'car_label' => 'BMW X5']);

        $this->actingAs($this->admin())
            ->get(route('admin.requests.index'))
            ->assertOk()
            ->assertSee('علی رضایی');
    }

    public function test_kanban_shows_existing_leads_without_filters(): void
    {
        $stage = PipelineStage::create(['name' => 'جدید', 'slug' => 'new', 'sort_order' => 1, 'is_active' => true]);
        QuoteRequest::create(['name' => 'سارا احمدی', 'phone' => '0935', 'car_label' => 'Toyota Camry', 'temperature' => 'warm', 'current_stage_id' => $stage->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.kanban'))
            ->assertOk()
            ->assertSee('سارا احمدی');
    }

    public function test_vin_checks_report_shows_existing_rows_without_filters(): void
    {
        VinCheck::create(['vin' => '1HGCM82633A123456', 'make' => 'Honda', 'verdict' => 'آمریکا']);

        $this->actingAs($this->admin())
            ->get(route('admin.vin-checks.index'))
            ->assertOk()
            ->assertSee('1HGCM82633A123456');
    }

    public function test_activity_log_shows_lines_without_filters(): void
    {
        ActivityLogger::info('رویداد تستی برای اطمینان از نمایش لاگ');

        $this->actingAs($this->admin())
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertSee('رویداد تستی برای اطمینان از نمایش لاگ');
    }

    public function test_export_calculations_type_returns_calculation_columns_not_requests(): void
    {
        CalculationLog::create(['car_label' => 'Kia Sportage', 'category' => 'c1500', 'total_with_profit' => 500000000]);
        QuoteRequest::create(['name' => 'کاربر دیگر', 'phone' => '0999', 'car_label' => 'Nissan']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.export', ['type' => 'calculations']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Kia Sportage', $csv);
        $this->assertStringNotContainsString('کاربر دیگر', $csv);
    }

    public function test_requests_filter_by_search_term_narrows_results(): void
    {
        QuoteRequest::create(['name' => 'John Smith', 'phone' => '001', 'car_label' => 'BMW']);
        QuoteRequest::create(['name' => 'Jane Doe', 'phone' => '002', 'car_label' => 'Audi']);

        $this->actingAs($this->admin())
            ->get(route('admin.requests.index', ['q' => 'John']))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertDontSee('Jane Doe');
    }
}
