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
        $devices = Device::select(['id', 'status'])->get();

        foreach ($users as $user) {
            DB::transaction(function () use ($user, $devices) {
                try {
                    // Ensure last_email_sent is a Carbon object
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
        $onlineCount = $devices->where('status', 'online')->count();
        $shortOfflineCount = $devices->where('status', 'offline_short_term')->count();
        $longOfflineCount = $devices->where('status', 'offline_long_term')->count();

        return view('emails.device_status', compact(
            'onlineCount', 'shortOfflineCount', 'longOfflineCount', 'devices', 'lastEmailSent', 'emailFrequencyHours'
        ))->render();
    }
}
