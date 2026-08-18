<?php

namespace App\Services;

use App\Models\MobileAccessToken;
use App\Models\MobileCustomer;

final class MobileTokenAuthenticator
{
    /** @return array{customer: MobileCustomer, token: MobileAccessToken}|null */
    public function resolve(?string $plainToken): ?array
    {
        if (! is_string($plainToken) || ! preg_match('/^(\d+)\|([A-Za-z0-9_-]{43})$/', $plainToken, $matches)) {
            return null;
        }

        $token = MobileAccessToken::with('mobileCustomer')->find((int) $matches[1]);
        if (! $token || $token->expires_at->isPast()) {
            return null;
        }

        $candidate = hash('sha256', $matches[2]);
        if (! hash_equals($token->token_hash, $candidate)) {
            return null;
        }

        return ['customer' => $token->mobileCustomer, 'token' => $token];
    }
}
