<?php

namespace Karabin\Fabriq;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Karabin\Fabriq\Http\Middleware\LocaleMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('locale', LocaleMiddleware::class);

        $router->aliasMiddleware('role', RoleMiddleware::class);
    }
}
