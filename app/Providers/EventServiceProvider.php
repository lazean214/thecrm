<?php

namespace App\Providers;

use App\Events\DealStageChanged;
use App\Listeners\HandleDealStageAutomations;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<class-string>>
     */
    protected $listen = [
        DealStageChanged::class => [
            HandleDealStageAutomations::class,
        ],
    ];

    /**
     * Register any other events for your application.
     */
    public function boot(): void
    {
        //
    }
}
