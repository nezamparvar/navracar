<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'pageTitle' => 'تنظیمات نرخ ارز',
            'pageSubtitle' => 'این نرخ‌ها به‌صورت زنده در صفحات «قیمت خودروها» و برای نمایش نرخ روز به مشتری استفاده می‌شوند.',
            'freeRate' => Setting::get(Setting::FREE_RATE),
            'customsRate' => Setting::get(Setting::CUSTOMS_RATE),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'free_rate' => ['required', 'numeric', 'min:1'],
            'customs_rate' => ['required', 'numeric', 'min:1'],
        ]);

        Setting::set(Setting::FREE_RATE, (string) $data['free_rate']);
        Setting::set(Setting::CUSTOMS_RATE, (string) $data['customs_rate']);

        return back()->with('success', 'نرخ‌های ارز به‌روزرسانی شد.');
    }
}
