<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehiclePricingController extends Controller
{
    public function __invoke(Request $request, VehiclePricingService $pricing)
    {
        $data = $request->validate([
            'real_price_aed' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'customs_price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'category' => ['required', Rule::in(VehiclePricingCatalog::categoryIds())],
        ]);

        $result = $pricing->calculate($pricing->inputFromArray($data));

        return response()->json([
            ...$result->toArray(),
            'publicSummary' => $result->publicDisplaySummary(),
        ]);
    }
}
