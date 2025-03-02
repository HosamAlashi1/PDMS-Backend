<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\PingDeviceJob;

class DeviceStatusService
{
    public function execute()
    {
        $jobs = Device::all()->map(function ($device) {
            return new PingDeviceJob($device);
        })->toArray();

        if (!empty($jobs)) {
            Bus::batch($jobs)->dispatch();
        } else {
            Log::info("No jobs to process in batch.");
        }
    }


    private function processBatchesSequentially(array $batchJobs, int $index = 0)
    {
        if ($index >= count($batchJobs)) {
            return; // Stop when all batches are processed
        }

        if (!is_array($batchJobs[$index]) || empty($batchJobs[$index])) {
            Log::error("Batch job at index $index is not a valid array or is empty.");
            return;
        }

        Bus::batch(array_values($batchJobs[$index])) // Ensure valid job array
        ->then(function () use ($batchJobs, $index) {
            $this->processBatchesSequentially($batchJobs, $index + 1);
        })
            ->dispatch();
    }


    public function pingWithDynamicTimeout(Device $device)
    {
        $timeouts = [2000, 3000, 5000, 7000]; // Multiple attempt timeouts
        $finalStatus = 'unknown';
        $responseTime = 0;

        foreach ($timeouts as $timeout) {
            try {
                $pingResult = $this->pingDevice($device->ip_address, $timeout);

                if ($pingResult['status'] === 'success') {
                    $finalStatus = 'success';
                    $responseTime = $pingResult['time'];
                    break;
                }
            } catch (\Exception $e) {
                Log::error("Ping failed for device {$device->ip_address}: " . $e->getMessage());
                $finalStatus = 'timeout';
            }
        }

        // Update device status in the database
        $this->updateDeviceStatus($device, $finalStatus, $responseTime);
    }

    private function pingDevice(string $ip, int $timeout)
    {
        $process = new \Symfony\Component\Process\Process(["ping", "-c", "1", "-W", $timeout / 1000, $ip]);
        $process->run();

        if (!$process->isSuccessful()) {
            return ['status' => 'timeout', 'time' => 0];
        }

        preg_match('/time=(\d+(\.\d+)?) ms/', $process->getOutput(), $matches);
        return isset($matches[1]) ? ['status' => 'success', 'time' => (int) $matches[1]] : ['status' => 'timeout', 'time' => 0];
    }

    private function updateDeviceStatus(Device $device, string $status, int $responseTime)
    {
        DB::transaction(function () use ($device, $status, $responseTime) {
            $device->response_time = $responseTime;
            $device->last_examination_date = now();

            if ($status === 'success') {
                $this->handleOnlineDevice($device);
            } else {
                $this->handleOfflineDevice($device);
            }

            $device->save();
        });
    }

    private function handleOnlineDevice(Device $device)
    {
        $device->online_since = $device->online_since ?: now();
        $device->offline_since = null;
        $device->count = 0;
        $device->status = 'online';
    }

    private function handleOfflineDevice(Device $device)
    {
        $device->offline_since = $device->offline_since ?: now();
        $device->count++;

        $duration = now()->diffInHours($device->offline_since);
        $device->status = $duration >= 24 ? 'offline_long_term' : 'offline_short_term';
    }
}
