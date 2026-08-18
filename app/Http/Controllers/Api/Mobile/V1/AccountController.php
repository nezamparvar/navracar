<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['customer' => AuthController::customer($request->attributes->get('mobile_customer'))]);
    }

    public function update(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('mobile_customer');
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]+$/', Rule::unique('mobile_customers')->ignore($customer->id)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('mobile_customers')->ignore($customer->id)],
        ]);
        $customer->update($data);

        return response()->json(['customer' => AuthController::customer($customer->refresh())]);
    }
}
