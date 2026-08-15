<?php

namespace App\Http\Controllers\Admin;

use App\Models\BrowserExtensionPairing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExtensionPairingController extends Controller
{
    public function index(): View
    {
        $pairings = BrowserExtensionPairing::with('adminUser')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.extension-pairing.index', compact('pairings'));
    }

    public function create(): View
    {
        return view('admin.extension-pairing.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_user_id' => ['nullable', 'exists:admin_users,id'],
            'environment' => ['required', 'in:staging,production'],
            'expires_in_hours' => ['required', 'integer', 'min:1', 'max:8760'],
        ]);

        $pairingCode = BrowserExtensionPairing::generatePairingCode();
        $token = BrowserExtensionPairing::generateToken();

        BrowserExtensionPairing::create([
            'admin_user_id' => $data['admin_user_id'],
            'pairing_code' => $pairingCode,
            'token' => $token,
            'device_name' => 'Browser Extension',
            'environment' => $data['environment'],
            'expires_at' => now()->addHours($data['expires_in_hours']),
        ]);

        return redirect()->route('admin.extension-pairing.index')
            ->with('success', "Pairing code generated: $pairingCode");
    }

    public function revoke(BrowserExtensionPairing $pairing): RedirectResponse
    {
        $pairing->update(['revoked_at' => now()]);

        return redirect()->back()->with('success', 'Pairing code revoked');
    }

    public function show(BrowserExtensionPairing $pairing): View
    {
        return view('admin.extension-pairing.show', compact('pairing'));
    }
}
