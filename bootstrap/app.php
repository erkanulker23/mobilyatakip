<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\TestFullFlowCommand::class,
        \App\Console\Commands\PruneAuditLogsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Forge / nginx arkasında HTTPS ve oturum çerezlerinin doğru ayarlanması için
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
        ]);
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\RestrictWorkshopUser::class,
            \App\Http\Middleware\TrackLastLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = 'Yüklenen dosya sunucu limitini aşıyor. Daha küçük bir fotoğraf seçin veya sunucuda client_max_body_size / upload_max_filesize artırın.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return back()->withInput()->with('error', $message);
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 && ! $e->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            $message = 'Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar giriş yapın.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            if ($request->routeIs('login') || $request->is('login')) {
                return redirect()->route('login')
                    ->withInput($request->except('_token', 'password'))
                    ->with('error', $message);
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password'))
                ->with('error', $message);
        });
    })->create();
