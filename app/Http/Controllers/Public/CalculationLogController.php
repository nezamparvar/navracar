<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

class CalculationLogController extends Controller
{
    public function store(Request $request, GeoLookupService $geo)
    {
        $geoData = $geo->lookup($request->ip());

        CalculationLog::create([
            'car_label' => mb_substr(trim((string) $request->input('car', '')), 0, 255),
            'category' => mb_substr(trim((string) $request->input('category', '')), 0, 100),
            'real_price_aed' => (float) $request->input('realPriceAED', 0),
            'customs_price_aed' => (float) $request->input('customsPriceAED', 0),
            'free_rate' => (float) $request->input('freeRate', 0),
            'customs_rate' => (float) $request->input('customsRate', 0),
            'sea_freight_aed' => (float) $request->input('seaFreightAED', 0),
            'permits_aed' => (float) $request->input('permitsAED', 0),
            'storage_toman' => (float) $request->input('storage', 0),
            'sum_customs' => (float) $request->input('sumCustoms', 0),
            'sum_plate' => (float) $request->input('sumPlate', 0),
            'total_no_profit' => (float) $request->input('totalNoProfit', 0),
            'service_profit' => (float) $request->input('serviceProfit', 0),
            'total_with_profit' => (float) $request->input('totalWithProfit', 0),
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['success' => true]);
    }
}
