<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()->hasActiveAccess(), 403, 'Your account does not have active access.');

        return $next($request);
    }
}
