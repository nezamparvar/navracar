<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\Post;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $listings = CarListing::published()->get(['slug', 'updated_at']);
        $posts = Post::published()->get(['slug', 'updated_at']);

        $staticUrls = [
            route('public.home'),
            route('public.calculator'),
            route('public.car-prices.index'),
            route('public.blog.index'),
        ];

        foreach (VehiclePricingCatalog::CATEGORIES as $id => $cat) {
            $staticUrls[] = route('public.car-prices.category', $id);
        }
        foreach (CarListing::PRICE_BRACKETS as $id => $bracket) {
            $staticUrls[] = route('public.car-prices.price', $id);
        }
        foreach (CarListing::published()->distinct()->pluck('make') as $make) {
            if ($make) {
                $staticUrls[] = route('public.car-prices.brand', $make);
            }
        }

        $xml = view('public.sitemap', [
            'staticUrls' => $staticUrls,
            'listings' => $listings,
            'posts' => $posts,
        ])->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
