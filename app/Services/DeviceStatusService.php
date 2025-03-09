<?php

namespace App\Services;

use App\Enums\DevicesStatus;
use App\Models\Device;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\PingDeviceJob;

class DeviceStatusService
{
    public function execute()
    {
        Device::chunk(100, function ($devices) {
            $jobs = [];

            foreach ($devices as $device) {
                $jobs[] = new PingDeviceJob($device);
            }

            if (!empty($jobs)) {
                Bus::batch($jobs)->dispatch();
            }
        });

        Log::info("Device processing started.");
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
        // Prepare update data
        $updateData = [
            'response_time' => $responseTime,
            'last_examination_date' => now(),
        ];

        if ($status === 'success') {
            $updateData = array_merge($updateData, $this->getOnlineDeviceData($device));
        } else {
            $updateData = array_merge($updateData, $this->getOfflineDeviceData($device));
        }

        // Perform a single update query without transactions to avoid locking issues
        DB::table('devices')->where('id', $device->id)->update($updateData);
    }

    private function getOnlineDeviceData(Device $device): array
    {
        return [
            'online_since' => $device->online_since ?: now(),
            'offline_since' => null,
            'count' => 0,
            'status' => DevicesStatus::Online->value,
        ];
    }

    private function getOfflineDeviceData(Device $device): array
    {
        $offlineSince = $device->offline_since ?: now();
        $duration = now()->diffInHours($offlineSince);

        return [
            'offline_since' => $offlineSince,
            'count' => DB::raw("CASE WHEN count < 5 THEN count + 1 ELSE count END"),
            'status' => $duration >= 24 ? DevicesStatus::OfflineLongTerm->value : DevicesStatus::OfflineShortTerm->value,
        ];
    }

}
