<?php

namespace App\Http\Middleware;

use App\Support\WorkshopUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictWorkshopUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isAdmin() || ! $user->isWorkshop()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $personnel = $request->route('personnel');

        if (! WorkshopUser::canAccessRoute($user, $routeName, $personnel)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        return $next($request);
    }
}
