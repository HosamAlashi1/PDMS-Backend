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
        $schedule->call(function () {
            app(DeviceStatusService::class)->execute();
        })->name('device_status_check')->everyMinute()->withoutOverlapping();

        $schedule->job(new PeriodicEmailJob())->name('email_job')->everyTwoHours()->withoutOverlapping();
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
