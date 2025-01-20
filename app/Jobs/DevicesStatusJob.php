<?php

namespace App\Jobs;

use App\Services\DevicesStatusService;
use App\Models\ErrorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DevicesStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $devicesStatusService;

    public function __construct(DevicesStatusService $devicesStatusService)
    {
        $this->devicesStatusService = $devicesStatusService;
    }

    public function handle(): void
    {
        try {
            $this->devicesStatusService->execute();
        } catch (\Exception $ex) {
            ErrorLog::create([
                'message' => $ex->getMessage(),
                'stack_trace' => $ex->getTraceAsString(),
                'http_method' => 'Devices Status Job',
                'request_path' => 'N/A',
                'query_params' => 'N/A',
                'user_id' => 0,
                'insert_date' => now(),
            ]);
        }
    }
}

