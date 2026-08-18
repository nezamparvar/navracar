<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Support\MobileVehiclePresenter;
use Illuminate\Http\JsonResponse;

class BootstrapController extends Controller
{
    public function __invoke(MobileVehiclePresenter $presenter): JsonResponse
    {
        $updatedAt = Setting::query()->max('updated_at');

        return response()->json([
            'environment' => app()->environment(),
            'featured_vehicles' => CarListing::published()->with('images')->latest('published_at')->take(6)->get()
                ->map(fn (CarListing $listing) => $presenter->summary($listing))->values(),
            'categories' => collect(VehiclePricingCatalog::CATEGORIES)->map(fn (array $category, string $id) => [
                'id' => $id, 'label' => $category['label'],
            ])->values(),
            'rates' => [
                'aed_to_toman' => (float) Setting::get(Setting::FREE_RATE),
                'customs_aed_to_toman' => (float) Setting::get(Setting::CUSTOMS_RATE),
                'updated_at' => $updatedAt ? now()->parse($updatedAt)->toIso8601String() : null,
            ],
            'contact' => [
                'whatsapp_uae' => Setting::get(Setting::WHATSAPP_UAE),
                'whatsapp_iran' => Setting::get(Setting::WHATSAPP_IRAN),
                'phone' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
            ],
        ]);
    }
}
