<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Services\VehiclePricing\VehiclePricingSettings;

class CalculatorController extends Controller
{
    public function index()
    {
        $pricingSettings = VehiclePricingSettings::current();
        $listings = CarListing::published()->with('images')->latest('published_at')->get()
            ->map(fn (CarListing $l) => [
                'make' => $l->make,
                'title' => $l->title_fa,
                'price_aed' => (float) $l->price_aed,
                'url' => route('public.car-prices.show', $l),
                'cover' => $l->coverImage()?->url(),
            ])->values();

        return view('public.calculator', [
            'contactIran' => Setting::get(Setting::WHATSAPP_IRAN),
            'contactUae' => Setting::get(Setting::WHATSAPP_UAE),
            'contactTehran' => Setting::get(Setting::TEHRAN_OFFICE_PHONE),
            'carListings' => $listings,
            'menuItems' => MenuItem::active()->get(),
            'usdToAedRate' => (float) Setting::get(Setting::USD_TO_AED_RATE),
            'customsValueDiscountPercent' => (float) Setting::get(Setting::CUSTOMS_VALUE_DISCOUNT_PERCENT),
            'pricingSettings' => $pricingSettings->toArray(),
            'pricingUrl' => route('public.vehicle-pricing.calculate'),
        ]);
    }
}
