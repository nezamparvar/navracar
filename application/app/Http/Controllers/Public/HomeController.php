<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\HomeSlide;
use App\Models\Post;
use App\Services\VehiclePricing\VehiclePricingCatalog;

class HomeController extends Controller
{
    public function index()
    {
        return view('public.home', [
            'title' => 'ناوراکار | واردات خودرو از امارات به ایران',
            'slides' => HomeSlide::active()->get(),
            'latestListings' => CarListing::published()->with('images')->latest('published_at')->take(8)->get(),
            'latestPosts' => Post::published()->latest('published_at')->take(3)->get(),
            'categories' => VehiclePricingCatalog::CATEGORIES,
            'priceBrackets' => CarListing::PRICE_BRACKETS,
        ]);
    }
}
