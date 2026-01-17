<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use App\Observers\TaskObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Gate;
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
        // Global Gate bypass for super_admin role (God Mode)
        Gate::before(static function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        User::observe(UserObserver::class);
        Task::observe(TaskObserver::class);
    }
}
