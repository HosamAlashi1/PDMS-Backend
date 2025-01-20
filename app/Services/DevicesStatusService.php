<?php

namespace App\Services;

use App\Models\Device;
use App\Models\ErrorLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DevicesStatusService
{
    public function execute(): void
    {
        // Retrieve all devices from the database
        $devices = Device::all();

        // Use parallel processing to handle devices
        $devices->each(function ($device) {
            $this->pingWithDynamicTimeout($device);
        });
    }

    private function pingWithDynamicTimeout(Device $device): void
    {
        $timeouts = [2000, 3000, 5000, 7000];
        $finalStatus = 'Unknown';
        $responseTime = 0;

        foreach ($timeouts as $timeout) {
            try {
                $pingResult = $this->ping($device->ip_address, $timeout);

                if ($pingResult['status'] === 'Success') {
                    $finalStatus = 'Success';
                    $responseTime = $pingResult['time'];
                    break;
                }
            } catch (HttpException $e) {
                $finalStatus = 'TimedOut';
            }
        }

        // Update device status
        $this->updateDeviceStatus($device, $finalStatus, $responseTime);
    }

    private function ping(string $ipAddress, int $timeout): array
    {
        // Simulate a ping request (use actual implementation if needed)
        $startTime = microtime(true);
        $pingResult = @fsockopen($ipAddress, 80, $errno, $errstr, $timeout / 1000);
        $endTime = microtime(true);

        if ($pingResult) {
            fclose($pingResult);
            return ['status' => 'Success', 'time' => ($endTime - $startTime) * 1000];
        }

        return ['status' => 'Failed', 'time' => 0];
    }

    private function updateDeviceStatus(Device $device, string $status, int $responseTime): void
    {
        if ($status === 'Success') {
            $this->handleOnlineDevice($device);
        } else {
            $this->handleOfflineDevice($device);
        }

        $device->response_time = $responseTime;
        $device->last_examination_date = now();

        $device->save();
    }

    private function handleOnlineDevice(Device $device): void
    {
        $device->online_since = $device->online_since ?? now();
        $device->offline_since = null;
        $device->count = 0;
        $device->status = 'Online';
    }

    private function handleOfflineDevice(Device $device): void
    {
        $device->offline_since = $device->offline_since ?? now();
        $device->count++;

        $duration = now()->diffInHours($device->offline_since);

        $device->status = $duration >= 24 ? 'OfflineLongTerm' : 'OfflineShortTerm';
    }
}
