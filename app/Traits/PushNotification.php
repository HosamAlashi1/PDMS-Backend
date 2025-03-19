<?php
namespace App\Traits;

use Exception;
use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait PushNotification {
    public function sendNotification($token, $title, $body, $data) {
        $fcmUrl = "https://fcm.googleapis.com/v1/projects/paltel---hm/messages:send";

        $notification = [
            'message' => [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default' // Optional: for making sound on notification arrival
                ],
                'data' => $data,
                'token' => $token,
                'android' => [
                    'priority' => 'high'
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10'
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'badge' => 1,
                            'sound' => 'default'
                        ]
                    ]
                ]
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($fcmUrl, $notification);

            return $response->json();
        } catch (Exception $e) {
            Log::error("Error sending push notification to token: {$token} - " . $e->getMessage());
            return false;
        }
    }

    private function getAccessToken() {
        $keyPath = config('services.firebase.key_path');  // Load the path to the service account JSON file from config
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyPath);  // Set the environment variable for Google SDK

        // Define the scope needed for Firebase messaging
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        // Get the credentials using the SDK
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);

        // Fetch the auth token from the credentials
        $token = $credentials->fetchAuthToken();

        // Return the access token or null if it's not available
        return $token['access_token'] ?? null;
    }


}
