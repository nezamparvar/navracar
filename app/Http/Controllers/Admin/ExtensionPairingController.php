<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrowserExtensionPairing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExtensionPairingController extends Controller
{
    public function index(): View
    {
        return view('admin.extension-pairing.index', [
            'pageTitle' => 'اتصال افزونه مرورگر',
            'pairings' => BrowserExtensionPairing::with('adminUser')->latest()->paginate(50),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'environment' => ['required', 'in:staging,production'],
            'expires_in_hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);
        $issued = BrowserExtensionPairing::issue(
            $request->user(),
            $data['environment'],
            (int) $data['expires_in_hours'],
        );

        return redirect()->route('admin.extension-pairing.index')
            ->with('pairing_code', $issued['pairing_code'])
            ->with('success', 'کد اتصال یک‌بارمصرف ساخته شد.');
    }

    public function revoke(BrowserExtensionPairing $pairing): RedirectResponse
    {
        $pairing->update(['status' => 'revoked', 'revoked_at' => now(), 'token_hash' => null]);

        return back()->with('success', 'دسترسی افزونه لغو شد.');
    }
}
