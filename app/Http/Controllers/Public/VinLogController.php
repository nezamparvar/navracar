<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\VinCheck;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

class VinLogController extends Controller
{
    public function store(Request $request, GeoLookupService $geo)
    {
        $geoData = $geo->lookup($request->ip());

        VinCheck::create([
            'vin' => mb_substr(trim((string) $request->input('vin', '')), 0, 20),
            'make' => mb_substr(trim((string) $request->input('make', '')), 0, 255),
            'model' => mb_substr(trim((string) $request->input('model', '')), 0, 255),
            'model_year' => mb_substr(trim((string) $request->input('year', '')), 0, 10),
            'plant_country' => mb_substr(trim((string) $request->input('plantCountry', '')), 0, 255),
            'verdict' => mb_substr(trim((string) $request->input('verdict', '')), 0, 50),
            'source' => mb_substr(trim((string) $request->input('source', '')), 0, 30),
            'country' => $geoData['country'],
            'city' => $geoData['city'],
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }
}
