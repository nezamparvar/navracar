<?php

namespace App\Http\Controllers\Admin;

use App\Models\BrowserExtensionPairing;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class BrowserCaptureSettingsController extends Controller
{
    public function index()
    {
        $activePairings = BrowserExtensionPairing::where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('paired_at', 'desc')
            ->get();

        $pendingCodes = BrowserExtensionPairing::where('status', 'pending')
            ->where('created_at', '>', now()->subHours(1))
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.browser-capture.settings', [
            'activePairings' => $activePairings,
            'pendingCodes' => $pendingCodes,
        ]);
    }

    public function generatePairingCode(Request $request)
    {
        $validated = $request->validate([
            'environment' => 'required|in:staging,production',
        ]);

        $code = BrowserExtensionPairing::generatePairingCode();

        $pairing = BrowserExtensionPairing::create([
            'pairing_code' => $code,
            'environment' => $validated['environment'],
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'pairing_code' => $code,
            'environment' => $validated['environment'],
            'expires_in_minutes' => 60,
        ]);
    }

    public function exchangeCode(Request $request)
    {
        $validated = $request->validate([
            'pairing_code' => 'required|string|size:6',
            'environment' => 'required|in:staging,production',
            'device_name' => 'nullable|string|max:255',
        ]);

        $pairing = BrowserExtensionPairing::where('pairing_code', $validated['pairing_code'])
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (!$pairing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid, expired, or already used pairing code',
            ], 400);
        }

        if ($pairing->environment !== $validated['environment']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pairing code environment mismatch',
            ], 400);
        }

        $pairing->activate($validated['device_name'] ?? 'Extension Device');

        return response()->json([
            'status' => 'success',
            'token' => $pairing->extension_token,
            'environment' => $pairing->environment,
            'message' => 'Extension successfully paired',
        ]);
    }

    public function revokePairing(BrowserExtensionPairing $pairing)
    {
        if ($pairing->status === 'revoked') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pairing is already revoked',
            ], 400);
        }

        $pairing->revoke();

        return response()->json([
            'status' => 'success',
            'message' => 'Pairing revoked successfully',
        ]);
    }

    public function listPairings()
    {
        $pairings = BrowserExtensionPairing::where('status', '!=', 'pending')
            ->orderBy('paired_at', 'desc')
            ->paginate(20);

        return view('admin.browser-capture.pairings', [
            'pairings' => $pairings,
        ]);
    }
}
