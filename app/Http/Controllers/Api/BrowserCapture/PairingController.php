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
            'pairing_code' => ['required', 'digits:6'],
            'environment' => ['required', 'in:staging,production'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $result = BrowserExtensionPairing::exchange(
            $data['pairing_code'],
            $data['environment'],
            $data['device_name'] ?? null,
        );

        if ($result['status'] === 'environment_mismatch') {
            throw ValidationException::withMessages(['environment' => 'Pairing environment does not match.']);
        }
        if ($result['status'] !== 'success') {
            throw ValidationException::withMessages(['pairing_code' => 'Pairing code is invalid, expired, or already used.']);
        }

        return response()->json([
            'status' => 'success',
            'token' => $result['token'],
            'environment' => $result['pairing']->environment,
            'message' => 'Extension successfully paired.',
        ]);
    }
}
