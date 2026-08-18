<?php

namespace App\Services;

use App\Models\MobileAppInstallation;
use Illuminate\Http\Request;

final class MobileInstallationAuthenticator
{
    public function resolve(Request $request): ?MobileAppInstallation
    {
        $installationId = $request->header('X-Navracar-Installation');
        $secret = $request->header('X-Navracar-Installation-Secret');
        if (! is_string($installationId) || ! is_string($secret)) {
            return null;
        }

        $installation = MobileAppInstallation::where('installation_id', $installationId)->first();
        if (! $installation || ! hash_equals($installation->secret_hash, hash('sha256', $secret))) {
            return null;
        }

        return $installation;
    }
}
