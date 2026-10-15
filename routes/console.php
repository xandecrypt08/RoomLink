<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* Session reminder notifications (RLNK-30) */
Schedule::command('roomlink:send-session-reminders')
    ->everyMinute()
    ->withoutOverlapping();
