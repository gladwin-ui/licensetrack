<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:dispatch')->everyFiveMinutes()->withoutOverlapping();

// Urgent: kirim alert WhatsApp setiap 15 menit untuk lisensi yang kedaluwarsa hari ini
Schedule::command('reminders:dispatch-urgent')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
