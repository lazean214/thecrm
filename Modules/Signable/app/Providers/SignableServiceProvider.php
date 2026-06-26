<?php

namespace Modules\Signable\App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Signable\App\Http\Middleware\VerifySignableWebhookSignature;

class SignableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/config.php'), 'modules.signable');
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'signable');

        // Register webhook signature verification middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('signable.webhook', VerifySignableWebhookSignature::class);
    }

    private function modulePath(string $path = ''): string
    {
        $base = base_path('Modules/Signable');

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.$path;
    }
}
