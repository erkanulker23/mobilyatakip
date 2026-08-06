<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictWorkshopUser
{
    /** @var list<string> */
    private const ALLOWED_ROUTE_PREFIXES = [
        'workshop.',
        'reports.upcoming-due',
        'service-tickets.',
        'tasks.',
        'profile.',
        'notifications.',
        'api.user-tasks.',
        'logout',
        'assets.',
        'storage.',
        'company.',
    ];

    /** @var list<string> */
    private const BLOCKED_ROUTES = [
        'service-tickets.create',
        'service-tickets.store',
        'service-tickets.print',
        'service-tickets.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isAdmin() || ! $user->isWorkshop()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName === null) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        if (in_array($routeName, self::BLOCKED_ROUTES, true)) {
            abort(403, 'Bu sayfaya erişim yetkiniz yok.');
        }

        if (in_array($routeName, ['sales.workshop.koltuk', 'sales.workshop.mobilya'], true)) {
            return $next($request);
        }

        foreach (self::ALLOWED_ROUTE_PREFIXES as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                if (str_starts_with($routeName, 'reports.upcoming-due') && str_contains($routeName, 'print')) {
                    abort(403, 'Bu sayfaya erişim yetkiniz yok.');
                }

                return $next($request);
            }
        }

        if ($routeName === 'personnel.show') {
            $personnel = $request->route('personnel');
            if ($personnel && $user->personnel && $personnel->id === $user->personnel->id) {
                return $next($request);
            }
        }

        abort(403, 'Bu sayfaya erişim yetkiniz yok.');
    }
}
