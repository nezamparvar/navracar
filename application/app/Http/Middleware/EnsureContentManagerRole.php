<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureContentManagerRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->canManageContent()) {
            abort(403, 'دسترسی غیرمجاز — این بخش فقط برای مدیر سیستم یا مدیر محتوا است.');
        }

        return $next($request);
    }
}
