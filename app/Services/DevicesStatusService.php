<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function send(string $toEmail, string $subject, string $body): bool
    {
        try {
            // Fetch email settings from the database
            $settings = Setting::pluck('value', 'id');

            // Sender email and name
            $fromEmail = "t.samara@newsolutions.ps";
            $fromName = $settings[1] ?? 'Default Sender';

            // SMTP credentials
            $smtpHost = "smtp.sendgrid.net";
            $smtpPort = 587;
            $smtpUsername = "apikey";
            $smtpPassword = $settings[5] ?? '';

            // Mail setup
            $mailConfig = [
                'transport' => 'smtp',
                'host' => $smtpHost,
                'port' => $smtpPort,
                'encryption' => 'tls',
                'username' => $smtpUsername,
                'password' => $smtpPassword,
            ];

            config(['mail.mailers.smtp' => $mailConfig]);

            // Use Laravel's `html` method to send the email body as HTML
            Mail::mailer('smtp')->send([], [], function ($message) use ($toEmail, $fromEmail, $fromName, $subject, $body) {
                $message->from($fromEmail, $fromName)
                    ->to($toEmail)
                    ->subject($subject)
                    ->html($body); // Use the `html` method for the body
            });

            return true;
        } catch (\Exception $ex) {
            // Log the error for debugging purposes
            ErrorLog::create([
                'message' => $ex->getMessage(),
                'stack_trace' => $ex->getTraceAsString(),
                'http_method' => 'Email Service',
                'request_path' => 'N/A',
                'query_params' => 'N/A',
                'user_id' => 0,
                'insert_date' => now(),
            ]);

            return false;
        }
    }
}
