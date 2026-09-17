<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, fn () => config('services.stripe.secret')
            ? new StripeClient(config('services.stripe.secret'))
            : null);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(
            fn ($notifiable, string $token) => url("/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}")
        );
    }
}
