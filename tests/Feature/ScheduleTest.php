<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    public function test_purge_job_is_scheduled_daily_at_three_am_madrid(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $purgeEvent = collect($events)->first(function ($event) {
            return str_contains($event->description ?? '', 'PurgeExpiredFilesJob')
                || str_contains($event->command ?? '', 'PurgeExpiredFilesJob')
                || (isset($event->job) && $event->job instanceof \App\Jobs\PurgeExpiredFilesJob);
        });

        $this->assertNotNull($purgeEvent, 'PurgeExpiredFilesJob should be scheduled');
        $this->assertEquals('Europe/Madrid', $purgeEvent->timezone);
        $this->assertEquals('0 3 * * *', $purgeEvent->expression);
    }

    public function test_expiry_warning_is_scheduled_daily_at_nine_am_madrid(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        // El segundo evento scheduled es el aviso de expiración
        $warningEvent = collect($events)->first(function ($event) {
            return ($event->description ?? '') === ''
                && $event->expression === '0 9 * * *';
        });

        $this->assertNotNull($warningEvent, 'Expiry warning should be scheduled at 09:00');
        $this->assertEquals('Europe/Madrid', $warningEvent->timezone);
    }
}
