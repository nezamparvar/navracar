<?php

namespace App\Http\Middleware;

use App\Services\MobileTokenAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileCustomer
{
    public function __construct(private readonly MobileTokenAuthenticator $authenticator) {}

    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $this->authenticator->resolve($request->bearerToken());
        if (! $resolved) {
            return response()->json(['message' => 'برای ادامه دوباره وارد حساب شوید.'], 401);
        }

        $request->attributes->set('mobile_customer', $resolved['customer']);
        $request->attributes->set('mobile_access_token', $resolved['token']);
        $resolved['token']->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
