<?php

namespace App\Http\Middleware;

use App\Support\UserLastLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            UserLastLogin::record($user);
        }

        return $next($request);
    }
}
