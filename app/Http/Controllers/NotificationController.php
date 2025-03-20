<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\PushNotification;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use PushNotification;

    public function sendUserNotification(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'message' => 'required|string',
        ]);


        // Now you can use $validatedData['user_id'], etc., if you need to.

        $user = User::findOrFail($request->user_id);
        $token = $user->fcm_token;

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No FCM token found for user.']);
        }

        $data = ['info' => 'Test data'];  // Additional data you might want to include
        $result = $this->sendNotification($token, $request->title, $request->message, $data);

        if ($result) {
            return response()->json(['success' => true, 'message' => 'Notification sent successfully.', 'response' => $result]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to send notification.']);
    }


}
