<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\DeviceStatusService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PingDeviceJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Device $device;

    public function __construct(Device $device)
    {
        $this->device = $device;
    }

    public function handle()
    {
        $service = app(DeviceStatusService::class);
        $service->pingWithDynamicTimeout($this->device);
    }
}
