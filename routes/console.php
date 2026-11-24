<?php

declare(strict_types=1);

use App\Console\Commands\ArchiveAuditMonth;
use App\Console\Commands\AuditCleanupDue;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Private audit archiving runs monthly for the previous complete month. The
 * cleanup schedule is deliberately report-only: destructive execution requires
 * an operator-supplied confirmation phrase and a current dry-run report.
 *
 * Deployment must run both `php artisan schedule:work` (or a real cron entry)
 * and a queue worker for `pcdr:audit:archive --queue` and lifecycle notifications.
 */
Schedule::command(ArchiveAuditMonth::class)
    ->monthlyOn((int) config('audit_archive.schedule.day', 2), (string) config('audit_archive.schedule.time', '02:30'))
    ->withoutOverlapping(180)
    ->onOneServer()
    ->runInBackground()
    ->when((bool) config('audit_archive.enabled', true));

Schedule::command(AuditCleanupDue::class)
    ->dailyAt((string) config('audit_archive.schedule.cleanup_time', '03:30'))
    ->withoutOverlapping(60)
    ->onOneServer()
    ->runInBackground()
    ->when((bool) config('audit_archive.enabled', true));
