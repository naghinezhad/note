<?php

use App\Jobs\DeleteExpiredOtps;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new DeleteExpiredOtps)->daily();

Schedule::command('backup:run')->dailyAt('00:00');
