<?php

namespace App\Providers;

use App\Services\AiService;
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
        $this->app->singleton(AiService::class, function ($app) {
            return new AiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 pagination views
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // طلبات Livewire تخضع أيضاً لفرض المصادقة الثنائية
        \Livewire\Livewire::setUpdateRoute(function ($handle, $path) {
            return \Illuminate\Support\Facades\Route::post($path, $handle)->middleware(['web', 'check.2fa']);
        });

        // مدير النظام يملك كل الصلاحيات تلقائياً
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
        // Blade directives for role checking
        Blade::if('role', function (string $role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function (string $roles) {
            $roleArray = array_map('trim', explode('|', $roles));
            return auth()->check() && auth()->user()->hasAnyRole($roleArray);
        });

        Blade::if('permission', function (string $permission) {
            return auth()->check() && auth()->user()->hasPermissionTo($permission);
        });

        // View composer for global data
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $view->with('currentUser', $user);
                $view->with('userRoles', $user->getRoleNames());
                $view->with('unreadNotificationsCount', $user->unreadNotifications()->count());
            }
        });
    }
}
