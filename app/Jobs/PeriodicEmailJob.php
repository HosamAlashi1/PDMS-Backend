<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\User;
use App\Models\ErrorLog;
use App\Services\EmailService;
use App\Services\EmailTemplateService;
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
            DB::transaction(function () {
                $this->sendUserEmails();
            });
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
            $lastSent = $user->last_email_sent;
            $nextSendTime = $lastSent->addHours($user->email_frequency_hours);

            if (now() >= $nextSendTime && $devices->count() > 0) {
                $emailTitle = "Devices Status Report";
                $emailBody = $this->buildEmailBody($devices, $user->last_email_sent, $user->email_frequency_hours);

                $body = $this->emailTemplateService->basicTemplate($user->first_name, $emailTitle, $emailBody);
                $this->emailService->send($user->company_email, $emailTitle, $body);

                $user->update(['last_email_sent' => now()]);
            }
        }
    }

    private function buildEmailBody($devices, $lastEmailSent, $emailFrequencyHours)
    {
        $onlineCount = $devices->where('status', 'online')->count();
        $shortOfflineCount = $devices->where('status', 'offline_short_term')->count();
        $longOfflineCount = $devices->where('status', 'offline_long_term')->count();

        return view('emails.device_status', compact(
            'onlineCount', 'shortOfflineCount', 'longOfflineCount', 'devices', 'lastEmailSent', 'emailFrequencyHours'
        ))->render();
    }
}
