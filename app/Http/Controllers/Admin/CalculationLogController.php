<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationLog;
use Illuminate\Http\Request;

class CalculationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = CalculationLog::query();

        if ($from = $request->string('from', '')) {
            $query->where('created_at', '>=', $from.' 00:00:00');
        }
        if ($to = $request->string('to', '')) {
            $query->where('created_at', '<=', $to.' 23:59:59');
        }
        if ($cat = $request->string('cat', '')) {
            $query->where('category', $cat);
        }

        $rows = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $categories = CalculationLog::whereNotNull('category')->where('category', '!=', '')
            ->distinct()->orderBy('category')->pluck('category');

        return view('admin.calculations.index', [
            'pageTitle' => 'محاسبات ثبت‌شده',
            'rows' => $rows,
            'categories' => $categories,
            'filters' => $request->only(['from', 'to', 'cat']),
        ]);
    }
}
