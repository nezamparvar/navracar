<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filter = (string) $request->string('level', '');
        $search = (string) $request->string('q', '');

        $lines = collect(ActivityLogger::tail(1000));

        if ($filter !== '') {
            $lines = $lines->filter(fn ($l) => stripos($l, '['.strtoupper($filter).']') !== false);
        }
        if ($search !== '') {
            $lines = $lines->filter(fn ($l) => mb_stripos($l, $search) !== false);
        }

        return view('admin.activity-log.index', [
            'pageTitle' => 'لاگ فعالیت سایت',
            'lines' => $lines->take(300)->values(),
            'filter' => $filter,
            'search' => $search,
        ]);
    }
}
