<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use App\Services\GeoLookupService;
use App\Services\VehiclePricing\VehiclePricingCatalog;
use App\Services\VehiclePricing\VehiclePricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalculationLogController extends Controller
{
    public function store(Request $request, GeoLookupService $geo, VehiclePricingService $pricing)
    {
        $data = $request->validate([
            'car' => ['nullable', 'string', 'max:255'],
            'pricing' => ['required', 'array'],
            'pricing.real_price_aed' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'pricing.customs_price_aed' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'pricing.category' => ['required', Rule::in(VehiclePricingCatalog::categoryIds())],
        ]);

        $result = $pricing->calculate($pricing->inputFromArray($data['pricing']));
        $snapshot = $result->settingsSnapshot;
        $geoData = $geo->lookup($request->ip());

        CalculationLog::create([
            'car_label' => mb_substr(trim((string) ($data['car'] ?? '')), 0, 255),
            'category' => $result->category['id'],
            'real_price_aed' => $result->input['realPriceAed'],
            'customs_price_aed' => $result->input['customsPriceAed'],
            'free_rate' => $snapshot['freeRate'],
            'customs_rate' => $snapshot['customsRate'],
            'sea_freight_aed' => $snapshot['seaFreightAed'],
            'permits_aed' => $snapshot['licenseFeeAed'],
            'storage_toman' => $snapshot['storageToman'],
            'sum_customs' => $result->totals['customsSubtotalToman'],
            'sum_plate' => $result->totals['plateSubtotalToman'],
            'total_no_profit' => $result->totals['preServiceTotalToman'],
            'service_profit' => $result->totals['serviceFeeToman'],
            'total_with_profit' => $result->totals['finalTotalToman'],
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['success' => true, 'pricing' => $result->toArray()]);
    }
}
