<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\PipelineStage;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_form_renders(): void
    {
        $this->get(route('public.track.find'))->assertOk()->assertSee('پیگیری درخواست');
    }

    public function test_lookup_with_correct_number_and_phone_redirects_to_show(): void
    {
        $quoteRequest = QuoteRequest::create([
            'name' => 'مشتری تست', 'phone' => '09120000000', 'car_label' => 'تست',
            'category' => 'c2000', 'breakdown_json' => '[]', 'totals_json' => '{}',
            'total_with_profit' => 1000000, 'source' => 'test',
        ]);

        $this->get(route('public.track.find', ['number' => $quoteRequest->id, 'phone' => '09120000000']))
            ->assertRedirect(route('public.track.show', ['quoteRequest' => $quoteRequest->id, 'phone' => '09120000000']));
    }

    public function test_lookup_with_wrong_phone_shows_not_found(): void
    {
        $quoteRequest = QuoteRequest::create([
            'name' => 'مشتری تست', 'phone' => '09120000000', 'car_label' => 'تست',
            'category' => 'c2000', 'breakdown_json' => '[]', 'totals_json' => '{}',
            'total_with_profit' => 1000000, 'source' => 'test',
        ]);

        $this->get(route('public.track.find', ['number' => $quoteRequest->id, 'phone' => '09129999999']))
            ->assertOk()->assertSee('پیدا نشد');
    }

    public function test_show_requires_matching_phone_query_param(): void
    {
        $quoteRequest = QuoteRequest::create([
            'name' => 'مشتری تست', 'phone' => '09120000000', 'car_label' => 'تست',
            'category' => 'c2000', 'breakdown_json' => '[]', 'totals_json' => '{}',
            'total_with_profit' => 1000000, 'source' => 'test',
        ]);

        $this->get(route('public.track.show', ['quoteRequest' => $quoteRequest->id]))->assertNotFound();
        $this->get(route('public.track.show', ['quoteRequest' => $quoteRequest->id, 'phone' => 'wrong']))->assertNotFound();
    }

    public function test_show_displays_vehicle_stage_and_latest_invoice(): void
    {
        $stage = PipelineStage::create(['name' => 'سرنخ جدید', 'slug' => 'new-lead', 'sort_order' => 1, 'sla_hours' => 24]);

        $quoteRequest = QuoteRequest::create([
            'name' => 'مشتری تست', 'phone' => '09120000000', 'car_label' => 'بی‌ام‌و X4',
            'category' => 'c2000', 'breakdown_json' => '[]', 'totals_json' => '{}',
            'total_with_profit' => 1000000, 'source' => 'test', 'current_stage_id' => $stage->id,
        ]);

        Invoice::create([
            'request_id' => $quoteRequest->id, 'invoice_number' => 'INV-1', 'customer_name' => 'مشتری تست',
            'customer_phone' => '09120000000', 'car_label' => 'بی‌ام‌و X4', 'category' => 'c2000',
            'breakdown_json' => '[]', 'total_amount' => 1200000, 'discount_amount' => 0,
            'currency' => 'toman', 'status' => 'ارسال‌شده',
        ]);

        $this->get(route('public.track.show', ['quoteRequest' => $quoteRequest->id, 'phone' => '09120000000']))
            ->assertOk()
            ->assertSee('بی‌ام‌و X4')
            ->assertSee('INV-1')
            ->assertSee('ارسال‌شده');
    }

    public function test_show_without_invoice_displays_honest_empty_state(): void
    {
        $quoteRequest = QuoteRequest::create([
            'name' => 'مشتری تست', 'phone' => '09120000000', 'car_label' => 'تست',
            'category' => 'c2000', 'breakdown_json' => '[]', 'totals_json' => '{}',
            'total_with_profit' => 1000000, 'source' => 'test',
        ]);

        $this->get(route('public.track.show', ['quoteRequest' => $quoteRequest->id, 'phone' => '09120000000']))
            ->assertOk()
            ->assertSee('هنوز صورت‌حسابی برای این درخواست صادر نشده است.');
    }
}
