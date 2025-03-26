<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\Forget;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\EmailService;
use App\Services\EmailTemplateService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $emailService;
    protected $emailTemplateService;

    public function __construct(EmailService $emailService, EmailTemplateService $emailTemplateService)
    {
        $this->emailService = $emailService;
        $this->emailTemplateService = $emailTemplateService;
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $user = User::where('company_email', $request->email)->where('is_delete', 0)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->successResponse(null, false, 'These credentials do not match our records!');
        }

        if (!$user->is_active) {
            return $this->successResponse(null, false, 'Please contact system administrator!');
        }

        // Handle the FCM token if provided
        if ($request->filled('fcm_token')) {
            $this->updateOrCreateToken($user->id, $request->input('device_id', null), $request->fcm_token);
        }

        // Gather permissions and other user details
        $permissionIds = RolePermission::where('role_id', $user->role_id)->pluck('permission_id');
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('code');

        $customClaims = [
            'sub' => $user->id,
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addYears(100)->timestamp,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($user);

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'personal_email' => $user->personal_email,
            'company_email' => $user->company_email,
            'phone' => $user->phone,
            'address' => $user->address,
            'image' => $user->image ? asset($user->image) : asset('images/default-user.jpg'),
        ];

        return $this->successResponse([
            'user' => $userData,
            'token' => $token,
            'permissions' => $permissions,
            'fcm_token' => $request->fcm_token ?? null, // Optionally return the FCM token
        ], true, 'Login successfully.');
    }


    public function updateOrCreateToken($userId, $deviceId, $fcmToken)
    {
        $fcmTokenRecord = FcmToken::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->first();

        if (!$fcmTokenRecord) {
            $fcmTokenRecord = new FcmToken();
            $fcmTokenRecord->fcm_token = $fcmToken;
            $fcmTokenRecord->device_id = $deviceId;
            $fcmTokenRecord->user_id = $userId;
            $fcmTokenRecord->save();
        } else {
            $fcmTokenRecord->fcm_token = $fcmToken;
            $fcmTokenRecord->is_active = true;
            $fcmTokenRecord->save();
        }
    }

    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $user = User::where(function ($query) use ($request) {
            $query->where('personal_email', $request->email)
                ->orWhere('company_email', $request->email);
        })->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'Email is incorrect!');
        }

        $forget = Forget::create([
            'user_id' => $user->id,
            'insert_date' => now(),
            'is_reset' => false,
        ]);

        $resetLink =  'http://www.paltelmonitor.com/auth/reset-password/' . $user->id;
        $body = $this->emailTemplateService->forgotPasswordTemplate($user->first_name, $resetLink);

        $emailToSend = $user->personal_email === $request->email ? $user->personal_email : $user->company_email;
        $this->emailService->send($emailToSend, 'Reset Password Request', $body);

        return $this->successResponse(null, true, 'Please check your email to reset your password.');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null,false, $validator->errors());
        }

        $forget = Forget::where('user_id', $request->id)->latest('insert_date')->first();

        if (!$forget || $forget->is_reset) {
            return $this->successResponse(null, false, 'Reset link is invalid!');
        }

        $user = User::where('id', $request->id)->where('is_delete', 0)->first();
        $user->update(['password' => Hash::make($request->password)]);

        $forget->update(['is_reset' => true, 'reset_date' => now()]);
        return $this->successResponse(null, true, 'Password reset successfully.');
    }

    public function changePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null,false, $validator->errors());
        }

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->successResponse(null, false, 'Password is incorrect!');
        }

        if ($request->old_password === $request->new_password) {
            return $this->successResponse(null, false, 'You cannot use an old password!');
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return $this->successResponse(null, true, 'Password changed successfully.');
    }


    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        // Use the authenticated user directly
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse(404, __('User does not exist'));
        }

        // Start a transaction to ensure all database changes are applied together
        DB::beginTransaction();

        try {
            $tokens = $user->fcmTokens()
                ->where('device_id', $request->device_id)
                ->where('is_active', true)
                ->get();

            foreach ($tokens as $token) {
                $token->is_active = false;
                $token->expired_at = now();  // Using 'now()' helper for current timestamp
                $token->save();
            }

            // Commit the transaction
            DB::commit();

            return $this->successResponse([
                'tokens_deactivated' => $tokens->count()  // Provide count of deactivated tokens
            ], true, __('Logout successful'));
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            return $this->errorResponse(500, $e->getMessage());
        }
    }


}
