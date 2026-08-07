<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Setting;
use App\Services\DubizzleTranslator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CarPriceController extends Controller
{
    private const DISPLAY_SPEC_FIELDS = [
        'model_year', 'kilometers', 'body_type', 'fuel_type', 'transmission_type',
        'regional_specs', 'steering_side', 'seller_type', 'warranty', 'exterior_color',
        'interior_color', 'horsepower', 'engine_capacity_cc', 'no_of_cylinders',
        'doors', 'seating_capacity', 'location_text',
    ];

    public function index()
    {
        $listings = CarListing::published()->with('images')->latest('published_at')->paginate(12);

        return view('public.car-prices.index', [
            'title' => 'قیمت خودروها | ناوراکار',
            'listings' => $listings,
        ]);
    }

    public function show(Request $request, CarListing $carListing, DubizzleTranslator $translator)
    {
        if ($carListing->status !== 'published' && ! $request->user()) {
            abort(404);
        }

        $specs = [];
        foreach (self::DISPLAY_SPEC_FIELDS as $field) {
            if (! empty($carListing->$field)) {
                $specs[] = ['label' => $translator->label($field), 'value' => $carListing->$field];
            }
        }

        return view('public.car-prices.show', [
            'title' => $carListing->meta_title ?: ($carListing->title_fa.' | ناوراکار'),
            'listing' => $carListing->load('images'),
            'specs' => $specs,
            'freeRate' => (float) Setting::get(Setting::FREE_RATE),
            'customsRate' => (float) Setting::get(Setting::CUSTOMS_RATE),
        ]);
    }

    public function sitemap()
    {
        $listings = CarListing::published()->get(['slug', 'updated_at']);

        $xml = view('public.car-prices.sitemap', ['listings' => $listings])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
