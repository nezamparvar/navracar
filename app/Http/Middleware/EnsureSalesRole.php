<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->canManageSales()) {
            abort(403, 'دسترسی غیرمجاز — این بخش فقط برای مدیر سیستم یا کارشناس فروش است.');
        }

        return $next($request);
    }
}
