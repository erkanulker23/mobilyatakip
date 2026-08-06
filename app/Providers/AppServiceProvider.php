<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Support\ActivityMessage;
use App\Support\ProductionCommandGuard;
use Illuminate\Pagination\Paginator;
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
        ProductionCommandGuard::register();

        app()->setLocale(config('app.locale', 'tr'));
        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.tailwind');

        Blade::directive('money', function (string $expression) {
            return "<?php echo \\App\\Support\\Money::format($expression); ?>";
        });

        View::composer('*', function ($view): void {
            $user = auth()->user();
            $view->with('hideCommercialData', $user?->hideCommercialData() ?? false);
            $view->with('showCustomerNames', $user?->canSeeCustomerNames() ?? false);
            $view->with('showSalesPersonnel', $user?->canSeeSalesPersonnel() ?? false);
        });

        View::composer('layouts.app', function ($view) {
            if (! auth()->check()) {
                $view->with('recentActivities', collect());

                return;
            }

            $dismissedAt = auth()->user()->notificationsDismissedAt;

            $recentActivities = AuditLog::query()
                ->with('user')
                ->when($dismissedAt, fn ($q) => $q->where('createdAt', '>', $dismissedAt))
                ->orderByDesc('createdAt')
                ->limit(25)
                ->get()
                ->map(fn (AuditLog $log) => ActivityMessage::from($log));

            $view->with('recentActivities', $recentActivities);
        });
    }
}
