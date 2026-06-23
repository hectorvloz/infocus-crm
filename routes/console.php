<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:run-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:push')->everyMinute()->withoutOverlapping();
Schedule::command('mail:weekly-hours-summary')->weeklyOn(1, '08:20');
Schedule::command('mail:monthly-hours-summary')->monthlyOn(1, '08:30');
Schedule::command('system:send-critical-alerts')->dailyAt('08:40');
