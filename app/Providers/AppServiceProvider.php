<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Support\ActivityMessage;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('money', function (string $expression) {
            return "<?php echo \\App\\Support\\Money::format($expression); ?>";
        });

        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                $view->with('recentActivities', collect());

                return;
            }

            $recentActivities = AuditLog::query()
                ->with('user')
                ->orderByDesc('createdAt')
                ->limit(25)
                ->get()
                ->map(fn (AuditLog $log) => ActivityMessage::from($log));

            $view->with('recentActivities', $recentActivities);
        });
    }
}
