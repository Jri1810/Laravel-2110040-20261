<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Render (dan hosting sejenis) men-terminate TLS di load balancer,
        // sehingga PHP menerima request lewat HTTP. Tanpa ini, url() akan
        // menghasilkan http:// dan gambar foto profil diblokir di Android.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
