<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:update-balances')->hourly();

Schedule::command('app:fetch-parsian-bank-balances')->daily();

// Horizon metrics snapshot (runs every minute for real-time metrics)
Schedule::command('horizon:snapshot')->everyMinute();
