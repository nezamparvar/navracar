<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\MenuItem;
use App\Models\Setting;

class CalculatorController extends Controller
{
    public function index()
    {
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
        ]);
    }
}
