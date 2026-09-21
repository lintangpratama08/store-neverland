<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by((string) ($request->user()?->id ?: $request->ip())));

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by(strtolower((string) $request->input('nickname')).'|'.$request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)
            ->by($request->ip()));

        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip()));

        RateLimiter::for('presence', fn (Request $request) => Limit::perMinute(30)
            ->by($request->ip()));

        RateLimiter::for('reset-data', fn (Request $request) => Limit::perHour(2)
            ->by((string) $request->user()?->id));
    }
}
