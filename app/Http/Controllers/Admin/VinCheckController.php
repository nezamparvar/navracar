<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VinCheck;
use Illuminate\Http\Request;

class VinCheckController extends Controller
{
    public function index(Request $request)
    {
        $query = VinCheck::query();

        if ($from = (string) $request->string('from', '')) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = (string) $request->string('to', '')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }

        $rows = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $usCount = VinCheck::where('verdict', 'like', '%آمریکا%')->count();

        return view('admin.vin-checks.index', [
            'pageTitle' => 'استعلام‌های شماره شاسی (VIN)',
            'rows' => $rows,
            'usCount' => $usCount,
            'filters' => $request->only(['from', 'to']),
        ]);
    }
}
