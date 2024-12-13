<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
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
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return config('app.frontend_url') . '/verify-email?email=' . $notifiable->getEmailForVerification() .
                '&verification_url=' . urlencode(URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
                ) . '&email=' . $notifiable->getEmailForVerification());
        });}
}
