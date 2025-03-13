<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\User;
use App\Models\ErrorLog;
use App\Services\EmailService;
use App\Services\EmailTemplateService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PeriodicEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected EmailService $emailService;
    protected EmailTemplateService $emailTemplateService;

    public function __construct()
    {
        $this->emailService = app(EmailService::class);
        $this->emailTemplateService = app(EmailTemplateService::class);
    }

    public function handle()
    {
        try {
            $this->sendUserEmails();
        } catch (\Exception $ex) {
            Log::error("Periodic Email Job Error: " . $ex->getMessage());

            ErrorLog::create([
                'message' => $ex->getMessage(),
                'stack_trace' => $ex->getTraceAsString(),
                'http_method' => 'Periodic Email Job',
                'request_path' => 'N/A',
                'query_params' => 'N/A',
                'user_id' => 0,
                'insert_date' => now(),
            ]);
        }
    }

    private function sendUserEmails()
    {
        $users = User::where('receives_emails', true)->where('is_delete', false)->get();
        $devices = Device::all();

        foreach ($users as $user) {
            DB::transaction(function () use ($user, $devices) {
                try {
                    $lastSent = $user->last_email_sent ? Carbon::parse($user->last_email_sent) : now()->subHours($user->email_frequency_hours ?? 24);
                    $emailFrequency = $user->email_frequency_hours ?? 24;
                    $nextSendTime = $lastSent->addHours($emailFrequency);

                    if (now() >= $nextSendTime && $devices->count() > 0) {
                        $emailTitle = "Devices Status Report";
                        $emailBody = $this->buildEmailBody($devices, $lastSent, $emailFrequency);
                        $body = $this->emailTemplateService->basicTemplate($user->first_name, $emailTitle, $emailBody);

                        $this->emailService->send($user->company_email, $emailTitle, $body);

                        $user->update(['last_email_sent' => now()]);
                    }
                } catch (\Exception $ex) {
                    Log::error("Periodic Email Job Error for User ID: {$user->id}", [
                        'email' => $user->company_email,
                        'error' => $ex->getMessage(),
                        'trace' => $ex->getTraceAsString(),
                    ]);
                }
            });
        }
    }

    private function buildEmailBody($devices, $lastEmailSent, $emailFrequencyHours)
    {
        $onlineCount = $devices->where('status', DevicesStatus::Online)->count();
        $shortOfflineCount = $devices->where('status', DevicesStatus::OfflineShortTerm)->count();
        $longOfflineCount = $devices->where('status', DevicesStatus::OfflineLongTerm)->count();

        $shortOfflineDevices = $devices->where('status', DevicesStatus::OfflineShortTerm)->sortByDesc('offline_since');
        $longOfflineDevices = $devices->where('status', DevicesStatus::OfflineLongTerm)->sortByDesc('offline_since');

        $summary = "
        <p style='font-family: Arial, sans-serif; color: #555; font-size: 14px; margin-bottom: 30px;'>
            This email provides an overview of the system's current device performance and connectivity, highlighting online and offline statuses.
        </p>
        <h4 style='font-family: Arial, sans-serif; color: #333;'>Devices Summary</h4>
        <table style='width: 100%; text-align: center; border: 1px solid #ddd; border-collapse: collapse; margin-bottom: 30px;'>
            <tr style='background-color: #f4f4f4;'>
                <th style='padding: 10px;'>Online Devices</th>
                <th style='padding: 10px;'>Offline (Short-Term)</th>
                <th style='padding: 10px;'>Offline (Long-Term)</th>
            </tr>
            <tr>
                <td style='padding: 10px; color: ".($onlineCount > 0 ? "#4CAF50" : "#333").";'>$onlineCount</td>
                <td style='padding: 10px; color: ".($shortOfflineCount > 0 ? "#FF5722" : "#333").";'>$shortOfflineCount</td>
                <td style='padding: 10px; color: ".($longOfflineCount > 0 ? "#FF5722" : "#333").";'>$longOfflineCount</td>
            </tr>
        </table>
    ";

        return $summary;
    }

    private function generateDeviceTable($devices, $title, $lastEmailSent, $emailFrequencyHours)
    {
        if ($devices->isEmpty()) {
            return "<h4 style='font-family: Arial, sans-serif; color: #333;'>$title</h4>
                <p style='text-align: center;'>No devices available</p>";
        }

        $rows = "";
        foreach ($devices as $device) {
            $isNew = $device->offline_since && $this->isNewDevice($device->offline_since, $lastEmailSent, $emailFrequencyHours);
            $downtime = $device->offline_since ? $this->formatDowntime(now()->diff($device->offline_since)) : "N/A";

            $rows .= "
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; font-family: Arial, sans-serif;'>
                        {$device->name}
                        " . ($isNew ? "<span style='color: #FF5722; font-weight: bold; margin-left: 10px;'>(New)</span>" : "") . "
                    </td>
                    <td style='padding: 10px; border: 1px solid #ddd; font-family: Arial, sans-serif;'>{$device->line_code}</td>
                    <td style='padding: 10px; border: 1px solid #ddd; font-family: Arial, sans-serif;'>{$device->offline_since->format('d/m/Y h:i A')}</td>
                    <td style='padding: 10px; border: 1px solid #ddd; font-family: Arial, sans-serif;'>$downtime</td>
                </tr>
            ";
        }

        return "
            <h4 style='font-family: Arial, sans-serif; color: #333;'>$title</h4>
            <table style='width: 100%; border: 1px solid #ddd; border-collapse: collapse; margin-bottom: 30px;'>
                <tr style='background-color: #f4f4f4;'>
                    <th style='padding: 10px;'>Device Name</th>
                    <th style='padding: 10px;'>Line Code</th>
                    <th style='padding: 10px;'>Offline Since</th>
                    <th style='padding: 10px;'>Downtime</th>
                </tr>
                $rows
            </table>
        ";
    }

    private function isNewDevice($offlineSince, $lastEmailSent, $emailFrequencyHours)
    {
        return $offlineSince > $lastEmailSent && $offlineSince <= now()->subHours($emailFrequencyHours);
    }

    private function formatDowntime($duration)
    {
        return $duration->d > 0 ? "{$duration->d} d, {$duration->h} h" : ($duration->h > 0 ? "{$duration->h} h, {$duration->i} m" : "{$duration->i} m");
    }
}
