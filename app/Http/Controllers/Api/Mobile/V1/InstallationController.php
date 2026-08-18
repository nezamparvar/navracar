<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileAppInstallation;
use App\Services\GeoLookupService;
use App\Services\MobileInstallationAuthenticator;
use App\Services\MobileTokenAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallationController extends Controller
{
    public function upsert(
        Request $request,
        string $installationId,
        MobileInstallationAuthenticator $installationAuth,
        MobileTokenAuthenticator $customerAuth,
        GeoLookupService $geoLookup,
    ): JsonResponse {
        $request->merge(['installation_id' => $installationId]);
        $data = $request->validate([
            'installation_id' => ['required', 'uuid'],
            'secret' => ['sometimes', 'string', 'size:43', 'regex:/^[A-Za-z0-9_-]+$/'],
            'analytics_consent' => ['sometimes', 'boolean'],
            'device' => ['sometimes', 'array'],
            'device.manufacturer' => ['sometimes', 'nullable', 'string', 'max:80'],
            'device.model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'device.platform' => ['sometimes', 'nullable', 'string', 'max:30'],
            'device.os_version' => ['sometimes', 'nullable', 'string', 'max:40'],
            'device.app_version' => ['sometimes', 'nullable', 'string', 'max:40'],
            'device.locale' => ['sometimes', 'nullable', 'string', 'max:20'],
            'acquisition' => ['sometimes', 'array'],
            'acquisition.source' => ['sometimes', 'nullable', 'string', 'max:80'],
            'acquisition.campaign' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        $installation = MobileAppInstallation::where('installation_id', $installationId)->first();
        $created = $installation === null;
        if ($created) {
            if (! isset($data['secret'])) {
                return response()->json(['message' => 'secret برای ثبت نصب الزامی است.'], 422);
            }
            $geo = $geoLookup->lookup($request->ip());
            $installation = new MobileAppInstallation([
                'installation_id' => $installationId,
                'secret_hash' => hash('sha256', $data['secret']),
                'analytics_consent' => (bool) ($data['analytics_consent'] ?? false),
                'country' => $geo['country'],
                'city' => $geo['city'],
            ]);
        } elseif ($installationAuth->resolve($request)?->isNot($installation) ?? true) {
            return response()->json(['message' => 'نصب برنامه احراز نشد.'], 403);
        }

        $device = $data['device'] ?? [];
        $acquisition = $data['acquisition'] ?? [];
        $installation->fill(array_filter([
            'device_manufacturer' => $device['manufacturer'] ?? null,
            'device_model' => $device['model'] ?? null,
            'platform' => $device['platform'] ?? null,
            'os_version' => $device['os_version'] ?? null,
            'app_version' => $device['app_version'] ?? null,
            'locale' => $device['locale'] ?? null,
            'acquisition_source' => $acquisition['source'] ?? null,
            'acquisition_campaign' => $acquisition['campaign'] ?? null,
        ], static fn ($value) => $value !== null));

        if ($customer = $customerAuth->resolve($request->bearerToken())['customer'] ?? null) {
            $installation->mobile_customer_id = $customer->id;
        }
        $installation->save();

        return response()->json($this->payload($installation), $created ? 201 : 200);
    }

    public function consent(Request $request, string $installationId, MobileInstallationAuthenticator $auth): JsonResponse
    {
        $request->merge(['installation_id' => $installationId]);
        $request->validate([
            'installation_id' => ['required', 'uuid'],
            'analytics_consent' => ['sometimes', 'boolean'],
            'notifications_consent' => ['sometimes', 'boolean'],
        ]);
        $installation = $auth->resolve($request);
        if (! $installation || $installation->installation_id !== $installationId) {
            return response()->json(['message' => 'نصب برنامه احراز نشد.'], 403);
        }

        if ($request->has('analytics_consent')) {
            $installation->analytics_consent = $request->boolean('analytics_consent');
            if (! $installation->analytics_consent) {
                $installation->events()->delete();
                $installation->last_seen_at = null;
            }
        }
        if ($request->has('notifications_consent')) {
            $installation->notifications_consent = $request->boolean('notifications_consent');
            if (! $installation->notifications_consent) {
                $installation->push_token = null;
                $installation->push_token_hash = null;
                $installation->push_token_updated_at = null;
            }
        }
        $installation->save();

        return response()->json($this->payload($installation));
    }

    /** @return array<string, mixed> */
    private function payload(MobileAppInstallation $installation): array
    {
        return [
            'installation_id' => $installation->installation_id,
            'analytics_consent' => $installation->analytics_consent,
            'notifications_consent' => $installation->notifications_consent,
            'push_available' => filled(config('services.firebase.project_id'))
                && is_string(config('services.firebase.credentials'))
                && is_readable(config('services.firebase.credentials')),
        ];
    }
}
