<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use PushNotification;

    use Illuminate\Http\Request;
    use App\Models\User;
    use Illuminate\Support\Facades\Validator;

    public function sendUserNotification(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $user = User::with('fcmTokens')->findOrFail($validated['user_id']);

        if ($user->fcmTokens->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No FCM tokens found for user.']);
        }

        $data = ['info' => 'Test data'];  // Additional data you might want to include
        $allResults = [];
        $success = false;

        foreach ($user->fcmTokens as $token) {
            if ($token->is_active) {
                $result = $this->sendNotification($token->fcm_token, $validated['title'], $validated['message'], $data);
                $allResults[] = $result;
                $success |= !empty($result) && $result['success']; // Adjust according to actual API response structure
            }
        }

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Notifications sent successfully.', 'results' => $allResults]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send notifications.', 'results' => $allResults]);
    }


}
