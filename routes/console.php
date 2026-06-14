<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

// Check for Doc Sent deals that haven't progressed in 24+ hours
Schedule::command('deals:check-stale-stages')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('gdpr:anonymize-expired')->daily();
