<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileCustomer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/', 'unique:mobile_customers,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:mobile_customers,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $customer = MobileCustomer::create([
            ...$data,
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($data['password']),
        ]);

        return response()->json($this->tokenResponse($customer), 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
        $customer = MobileCustomer::query()
            ->where('phone', $data['login'])->orWhere('email', mb_strtolower($data['login']))->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password_hash)) {
            return response()->json(['message' => 'اطلاعات ورود درست نیست.'], 422);
        }

        return response()->json($this->tokenResponse($customer));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_access_token')->delete();

        return response()->json(null, 204);
    }

    private function tokenResponse(MobileCustomer $customer): array
    {
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = $customer->accessTokens()->create([
            'token_hash' => hash('sha256', $secret),
            'name' => 'Android',
            'expires_at' => now()->addDays(90),
        ]);

        return ['token' => $token->id.'|'.$secret, 'customer' => $this->customer($customer)];
    }

    public static function customer(MobileCustomer $customer): array
    {
        return ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email];
    }
}
