<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CalculationLog;
use App\Models\QuoteRequest;
use App\Models\VinCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDashboardScopingTest extends TestCase
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

    public function test_sales_dashboard_only_shows_own_leads(): void
    {
        $salesA = $this->makeUser('sales', 'sales-a-dashboard');
        $salesB = $this->makeUser('sales', 'sales-b-dashboard');

        $leadA = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);
        $leadA->forceFill(['created_at' => now()])->save();

        $leadB = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
        ]);
        $leadB->forceFill(['created_at' => now()])->save();

        $responseA = $this->actingAs($salesA)->get(route('admin.dashboard'));
        $responseB = $this->actingAs($salesB)->get(route('admin.dashboard'));

        $responseA->assertOk()->assertViewHas('newRequests', 1);
        $responseB->assertOk()->assertViewHas('newRequests', 1);
    }

    public function test_sales_cannot_see_calculation_logs(): void
    {
        $salesA = $this->makeUser('sales', 'sales-no-calcs');

        CalculationLog::create([
            'ip_address' => '127.0.0.1',
            'country' => 'IR',
            'city' => 'Tehran',
        ])->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($salesA)->get(route('admin.dashboard'));
        $response->assertOk()
            ->assertViewHas('todayCalcs', 0)
            ->assertViewHas('topCars', function ($topCars) {
                return $topCars->isEmpty();
            })
            ->assertViewHas('catDist', function ($catDist) {
                return $catDist->isEmpty();
            });
    }

    public function test_sales_cannot_see_vin_checks(): void
    {
        $salesA = $this->makeUser('sales', 'sales-no-vin');

        VinCheck::create([
            'vin' => 'ABC123',
        ])->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($salesA)->get(route('admin.dashboard'));
        $response->assertOk()->assertViewHas('todayVin', 0);
    }

    public function test_admin_sees_all_dashboard_data(): void
    {
        $admin = $this->makeUser('admin', 'admin-all-data');
        $salesA = $this->makeUser('sales', 'sales-a-for-admin');

        $req1 = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
        ]);
        $req1->forceFill(['created_at' => now()])->save();

        $req2 = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => null,
        ]);
        $req2->forceFill(['created_at' => now()])->save();

        $calcLog = CalculationLog::create([
            'ip_address' => '127.0.0.1',
            'country' => 'IR',
            'city' => 'Tehran',
        ]);
        $calcLog->forceFill(['created_at' => now()])->save();

        $vinCheck = VinCheck::create([
            'vin' => 'ABC123',
        ]);
        $vinCheck->forceFill(['created_at' => now()])->save();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk()
            ->assertViewHas('newRequests', 2)
            ->assertViewHas('todayCalcs', 1)
            ->assertViewHas('todayVin', 1)
            ->assertViewHas('unassignedCount', 1);
    }

    public function test_sales_dashboard_does_not_expose_other_seller_leads(): void
    {
        $salesA = $this->makeUser('sales', 'sales-a-isolation');
        $salesB = $this->makeUser('sales', 'sales-b-isolation');

        $leadA = QuoteRequest::create([
            'name' => 'Lead A',
            'phone' => '0910',
            'assigned_to' => $salesA->id,
            'total_with_profit' => 10000000,
        ]);
        $leadA->forceFill(['created_at' => now()])->save();

        $leadB = QuoteRequest::create([
            'name' => 'Lead B',
            'phone' => '0911',
            'assigned_to' => $salesB->id,
            'total_with_profit' => 20000000,
        ]);
        $leadB->forceFill(['created_at' => now()])->save();

        $responseA = $this->actingAs($salesA)->get(route('admin.dashboard'));
        $recentRequestsA = $responseA->viewData('recentRequests');

        $this->assertCount(1, $recentRequestsA);
        $this->assertSame($leadA->id, $recentRequestsA[0]->id);
    }
}
