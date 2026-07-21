<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The layout is hand-written Material CSS, not Bootstrap. The default
        // Bootstrap paginator markup would render as an unstyled vertical list,
        // so use the view that matches the .pager rules in the layout.
        Paginator::defaultView('vendor.pagination.workspace');
        Paginator::defaultSimpleView('vendor.pagination.workspace');

        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in production
        |--------------------------------------------------------------------------
        |
        | Dokploy/Traefik terminates SSL before sending the request to Laravel.
        | Without this setting, Laravel may generate HTTP routes and forms.
        |
        */

        // if ($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }
    }
}
