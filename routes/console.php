<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campaigns:collect-subscribers')->everyMinute();
Schedule::command('campaigns:send-emails')->everyMinute();
Schedule::command('campaigns:finalize-status')->everyFiveMinutes();
