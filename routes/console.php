<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ───────────────────────────────────────────────────────────────────────────
// Console commands & scheduled tasks
// ───────────────────────────────────────────────────────────────────────────

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Appointment reminders (T-1 day) ────────────────────────────────────────
Schedule::command('app:send-appointment-email-reminders')->dailyAt('18:00');
Schedule::command('whatsapp:appointment-reminders')->dailyAt('09:00');

// ── Recurring expenses auto-post ────────────────────────────────────────────
Schedule::command('accounts:run-recurring-expenses')->dailyAt('00:30');
