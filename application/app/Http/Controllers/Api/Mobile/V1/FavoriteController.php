<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Support\MobileVehiclePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request, MobileVehiclePresenter $presenter): JsonResponse
    {
        $items = $request->attributes->get('mobile_customer')->favorites()->published()->with('images')->get();

        return response()->json(['data' => $items->map(fn (CarListing $listing) => $presenter->summary($listing))->values()]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $listing = CarListing::published()->where('slug', $slug)->firstOrFail();
        $request->attributes->get('mobile_customer')->favorites()->syncWithoutDetaching([$listing->id]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        $listing = CarListing::published()->where('slug', $slug)->firstOrFail();
        $request->attributes->get('mobile_customer')->favorites()->detach($listing->id);

        return response()->json(null, 204);
    }
}
