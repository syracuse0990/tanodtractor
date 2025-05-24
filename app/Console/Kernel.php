<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('geo-fence-notification:cron')->everyFifteenSeconds();
        // $schedule->command('delete-geo-fence:cron')->daily();
        // $schedule->command('check-distance:cron')->hourly();
        // $schedule->command('update-tractor-distance:cron')->daily();
        // $schedule->command('inactive-tractors-alert:command')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
