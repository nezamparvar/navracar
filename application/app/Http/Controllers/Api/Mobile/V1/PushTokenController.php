<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileAppInstallation;
use App\Services\MobileInstallationAuthenticator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PushTokenController extends Controller
{
    public function store(Request $request, string $installationId, MobileInstallationAuthenticator $auth): Response
    {
        $data = $request->validate(['token' => ['required', 'string', 'min:20', 'max:4096']]);
        $installation = $this->installation($request, $installationId, $auth);
        if (! $installation->notifications_consent) {
            return response()->json(['message' => 'رضایت اعلان‌ها فعال نیست.'], 409);
        }

        $hash = hash('sha256', $data['token']);
        MobileAppInstallation::where('push_token_hash', $hash)->whereKeyNot($installation->id)->update([
            'push_token' => null, 'push_token_hash' => null, 'push_token_updated_at' => null,
        ]);
        $installation->update([
            'push_token' => $data['token'], 'push_token_hash' => $hash, 'push_token_updated_at' => now(),
        ]);

        return response()->noContent();
    }

    public function destroy(Request $request, string $installationId, MobileInstallationAuthenticator $auth): Response
    {
        $installation = $this->installation($request, $installationId, $auth);
        $installation->update(['push_token' => null, 'push_token_hash' => null, 'push_token_updated_at' => null]);

        return response()->noContent();
    }

    private function installation(Request $request, string $installationId, MobileInstallationAuthenticator $auth): MobileAppInstallation
    {
        $installation = $auth->resolve($request);
        abort_unless($installation && $installation->installation_id === $installationId, 403, 'نصب برنامه احراز نشد.');

        return $installation;
    }
}
