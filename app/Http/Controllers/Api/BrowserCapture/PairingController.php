<?php

namespace App\Http\Controllers\Api\BrowserCapture;

use App\Http\Controllers\Controller;
use App\Models\BrowserExtensionPairing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PairingController extends Controller
{
    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pairing_code' => ['required', 'string', 'size:6'],
            'environment' => ['required', 'in:staging,production'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $pairing = BrowserExtensionPairing::where('pairing_code', $data['pairing_code'])->first();

        if (!$pairing) {
            throw ValidationException::withMessages([
                'pairing_code' => 'Invalid pairing code.',
            ]);
        }

        if ($pairing->isRevoked()) {
            throw ValidationException::withMessages([
                'pairing_code' => 'This pairing code has been revoked.',
            ]);
        }

        if ($pairing->isExpired()) {
            throw ValidationException::withMessages([
                'pairing_code' => 'This pairing code has expired.',
            ]);
        }

        if ($pairing->environment !== $data['environment']) {
            throw ValidationException::withMessages([
                'environment' => 'Pairing code is for ' . $pairing->environment . ' environment only.',
            ]);
        }

        $pairing->update([
            'last_used_at' => now(),
            'device_name' => $data['device_name'] ?? $pairing->device_name,
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $pairing->token,
            'environment' => $pairing->environment,
            'message' => 'Extension successfully paired',
        ]);
    }
}
