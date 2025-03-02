<?php

namespace App\Console;

use App\Jobs\DevicesStatusJob;
use App\Jobs\PeriodicEmailJob;
use App\Services\DeviceStatusService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Run Device Status Check every minute
        $schedule->call(function () {
            app(DeviceStatusService::class)->execute();
        })->everyMinute();

        // Run Periodic Email Job every two hours
        $schedule->job(new PeriodicEmailJob())->everyTwoHours();
    }



    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
