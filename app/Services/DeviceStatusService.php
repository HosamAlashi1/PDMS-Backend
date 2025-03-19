<?php

namespace App\Services;

use App\Enums\DevicesStatus;
use App\Models\Device;
use App\Models\User;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeviceStatusService
{

    use PushNotification;
    public function execute()
    {
        $batchSize = 100;
        $devices = Device::select(['id', 'ip_address', 'online_since', 'offline_since', 'count', 'status'])->cursor();

        $deviceChunks = [];
        foreach ($devices as $device) {
            $deviceChunks[] = $device;

            if (count($deviceChunks) >= $batchSize) {
                $this->processDeviceBatch($deviceChunks);
                $deviceChunks = [];
            }
        }

        // Process remaining devices
        if (!empty($deviceChunks)) {
            $this->processDeviceBatch($deviceChunks);
        }

        Log::info("Device processing started asynchronously (optimized for performance).");
    }

    private function processDeviceBatch(array $devices)
    {
        $updateData = [];

        // Parallel execution of pings
        $processes = [];
        foreach ($devices as $device) {
            $process = new Process(["ping", "-c", "1", "-W", "1", $device->ip_address]);
            $process->start();
            $processes[$device->id] = [$process, $device];
        }

        // Wait for processes to finish
        foreach ($processes as $deviceId => [$process, $device]) {
            $process->wait();
            $pingResult = $this->parsePingOutput($process);

            if ($pingResult['status'] === 'success') {
                $updateData[$deviceId] = $this->getOnlineDeviceData($pingResult['time']);
            } else {
                $updateData[$deviceId] = $this->getOfflineDeviceData($device);  // Pass the device object
            }
        }


        // Bulk update in a single query
        $this->bulkUpdateDeviceStatus($updateData);
    }

    private function parsePingOutput(Process $process)
    {
        if (!$process->isSuccessful()) {
            return ['status' => 'timeout', 'time' => 0];
        }

        preg_match('/time=(\d+(\.\d+)?) ms/', $process->getOutput(), $matches);
        return isset($matches[1]) ? ['status' => 'success', 'time' => (int) $matches[1]] : ['status' => 'timeout', 'time' => 0];
    }

    private function bulkUpdateDeviceStatus(array $updateData)
    {
        foreach ($updateData as $deviceId => $data) {
            DB::table('devices')->where('id', $deviceId)->update($data);
        }
    }

    private function getOnlineDeviceData(int $responseTime): array
    {
        return [
            'response_time' => $responseTime,
            'last_examination_date' => now(),
            'online_since' => now(),
            'offline_since' => null,
            'count' => 0,
            'status' => DevicesStatus::Online->value,
        ];
    }

    private function updateDeviceStatusAndNotify(Device $device): void
    {
        $newCount = $device->count < 6 ? $device->count + 1 : $device->count;
        $previousStatus = $device->status;
        $newStatus = $newCount >= 5 ? DevicesStatus::OfflineLongTerm->value : DevicesStatus::OfflineShortTerm->value;

        // Update device data
        $device->update([
            'last_examination_date' => now(),
            'offline_since' => $device->offline_since ?? now(),
            'count' => $newCount,
            'status' => $newStatus,
        ]);

        // Check if status changed to Offline Long Term
        if ($previousStatus !== $newStatus && $newStatus === DevicesStatus::OfflineLongTerm->value) {
            $this->notifyUsersAboutOfflineStatus($device);
        }
    }


    private function notifyUsersAboutOfflineStatus(Device $device)
    {
        // Retrieve all active users with active FCM tokens
        $users = User::where('is_active', 1)->with(['fcmTokens' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        $title = "Device Offline Alert";
        $message = "Device {$device->ip} has been offline for a long term.";

        foreach ($users as $user) {
            foreach ($user->fcmTokens as $token) {
                if ($token->fcm_token) {
                    $this->sendNotification($token->fcm_token, $title, $message, ['device_id' => $device->id]);
                }
            }
        }
    }



}
