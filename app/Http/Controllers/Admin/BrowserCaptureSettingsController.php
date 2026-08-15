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

        $admin = Auth::user();
        $pairing = BrowserExtensionPairing::generatePairingCode($admin, $validated['environment']);

        return response()->json([
            'status' => 'success',
            'pairing_code' => $pairing->pairing_code,
            'environment' => $validated['environment'],
            'expires_in_minutes' => 60,
        ]);
    }

    public function exchangeCode(Request $request)
    {
        $validated = $request->validate([
            'pairing_code' => 'required|string|size:6',
            'device_name' => 'nullable|string|max:255',
            'device_fingerprint' => 'nullable|string|max:255',
        ]);

        $result = BrowserExtensionPairing::exchangeCodeForToken(
            $validated['pairing_code'],
            $validated['device_name'] ?? 'Extension Device',
            $validated['device_fingerprint'] ?? null
        );

        if ($result['status'] === 'error') {
            return response()->json($result, 400);
        }

        return response()->json([
            'status' => 'success',
            'token' => $result['token'],
            'environment' => $result['environment'],
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
