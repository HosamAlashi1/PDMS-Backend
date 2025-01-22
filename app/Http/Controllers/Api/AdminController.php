<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\EmailService;
use App\Services\EmailTemplateService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    use ApiResponseTrait;

    private $emailService;
    private $emailTemplateService;

    public function __construct(EmailService $emailService, EmailTemplateService $emailTemplateService)
    {
        $this->middleware('role:USERS');
        $this->middleware('role:VIEW_USERS')->only('list', 'details', 'profile');
        $this->middleware('role:ADD_USER')->only('add');
        $this->middleware('role:EDIT_USER')->only('edit', 'editProfile');
        $this->middleware('role:ACTIVE_USER')->only('active');
        $this->middleware('role:DELETE_USER')->only('delete');
        $this->emailService = $emailService;
        $this->emailTemplateService = $emailTemplateService;
    }

    public function list(Request $request)
    {
        $query = User::query()->where('is_delete', 0);

        if ($request->has('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->q . '%')
                    ->orWhere('middle_name', 'like', '%' . $request->q . '%')
                    ->orWhere('last_name', 'like', '%' . $request->q . '%')
                    ->orWhere('personal_email', 'like', '%' . $request->q . '%')
                    ->orWhere('company_email', 'like', '%' . $request->q . '%')
                    ->orWhere('phone', 'like', '%' . $request->q . '%');
            });
        }

        $users = $query->orderByDesc('id')->paginate($request->size ?? 10);

        $users->transform(function ($user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'company_email' => $user->company_email,
                'phone' => $user->phone,
                'address' => $user->address,
                'is_active' => $user->is_active,
                'receives_emails' => $user->receives_emails,
                'email_frequency_hours' => $user->email_frequency_hours,
                'role' => Role::find($user->role_id)->name ?? null,
                'image' => $user->image ? asset($user->image) : asset('images/default-user.png'),
            ];
        });

        $response = [
            'data' => $users->items(),
            'total_records' => $users->total(),
            'total_count' => $users->count(),
        ];

        return $this->successResponse($response, true, 'Data returned successfully.');
    }

    public function details($id)
    {
        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        $details = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'personal_email' => $user->personal_email,
            'company_email' => $user->company_email,
            'phone' => $user->phone,
            'marital_status' => $user->marital_status,
            'address' => $user->address,
            'role_id' => $user->role_id,
            'receives_emails' => $user->receives_emails,
            'email_frequency_hours' => $user->email_frequency_hours,
            'image' => $user->image ? asset($user->image) :asset('images/default-user.png'),
        ];

        return $this->successResponse($details, true, 'Data returned successfully.');
    }

    public function profile($id)
    {
        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        $profile = [
            'id' => $user->id,
            'name' => "{$user->first_name} {$user->middle_name} {$user->last_name}",
            'personal_email' => $user->personal_email,
            'company_email' => $user->company_email,
            'phone' => $user->phone,
            'marital_status' => $user->marital_status,
            'role' => Role::find($user->role_id)->name ?? null,
            'image' => $user->image ? asset($user->image) :asset('images/default-user.png'),
        ];

        return $this->successResponse($profile, true, 'Data returned successfully.');
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'company_email' => 'required|email|unique:users,company_email',
            'personal_email' => 'required|email|unique:users,personal_email',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $password = $this->generateRandomString(8);

        $newUser = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name ?? '',
            'last_name' => $request->last_name ?? '',
            'personal_email' => $request->personal_email ?? '',
            'company_email' => $request->company_email,
            'phone' => $request->phone ?? '',
            'address' => $request->address ?? '',
            'password' => Hash::make($password),
            'marital_status' => $request->marital_status ?? '',
            'role_id' => $request->role_id,
            'receives_emails' => filter_var($request->receives_emails, FILTER_VALIDATE_BOOLEAN),
            'email_frequency_hours' => $request->email_frequency_hours ?? 0,
            'is_active' => true,
            'is_logout' => false,
            'insert_user_id' => Auth::id(),
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $this->uploadImage('users', $file);
            $newUser->update(['image' => $path]);
        }

        $emailBody = $this->emailTemplateService->newUserTemplate($newUser->first_name, $newUser->company_email, $password);
        $this->emailService->send($newUser->company_email, 'Account Created', $emailBody);

        return $this->successResponse(null, true, 'User added successfully.');
    }

    public function edit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'personal_email' => 'nullable|email|max:255',
            'company_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'receives_emails' => 'nullable|boolean',
            'email_frequency_hours' => 'nullable|integer',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        // if ($user->id === 1) {
        //     return $this->successResponse(null, false, 'This operation is not permitted!');
        // }

        $user->update([
            'first_name' => $request->input('first_name', $user->first_name),
            'middle_name' => $request->input('middle_name', $user->middle_name),
            'last_name' => $request->input('last_name', $user->last_name),
            'personal_email' => $request->input('personal_email', $user->personal_email),
            'company_email' => $request->input('company_email', $user->company_email),
            'phone' => $request->input('phone', $user->phone),
            'address' => $request->input('address', $user->address),
            'marital_status' => $request->input('marital_status', $user->marital_status),
            'role_id' => $request->input('role_id', $user->role_id),
            'receives_emails' => $request->has('receives_emails') ? filter_var($request->receives_emails, FILTER_VALIDATE_BOOLEAN) : $user->receives_emails,
            'email_frequency_hours' => $request->input('email_frequency_hours', $user->email_frequency_hours),
            'update_user_id' => Auth::id(),
            'update_date' => now(),
        ]);

        if ($request->hasFile('file')) {
            $filePath = $this->uploadImage('users', $request->file('file'));
            $user->update(['image' => $filePath]);
        }

        return $this->successResponse(null, true, 'User updated successfully.');
    }

    public function editProfile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'personal_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        // if ($user->id === 1 && Auth::id() !== 1) {
        //     return $this->successResponse(null, false, 'This operation is not permitted!');
        // }

        $user->update([
            'first_name' => $request->input('first_name', $user->first_name),
            'middle_name' => $request->input('middle_name', $user->middle_name),
            'last_name' => $request->input('last_name', $user->last_name),
            'personal_email' => $request->input('personal_email', $user->personal_email),
            'phone' => $request->input('phone', $user->phone),
            'address' => $request->input('address', $user->address),
            'update_user_id' => Auth::id(),
            'update_date' => now(),
        ]);

        if ($request->hasFile('file')) {
            $filePath = $this->uploadImage('users', $request->file('file'));
            $user->update(['image' => $filePath]);
        }

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'personal_email' => $user->personal_email,
            'company_email' => $user->company_email,
            'phone' => $user->phone,
            'address' => $user->address,
            'image' => $user->image ? asset($user->image) : asset('images/default-user.png'),
        ];

        return $this->successResponse($userData, true, 'Your account updated successfully.');
    }


    public function invite($id)
    {
        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        // if ($user->id === 1) {
        //     return $this->successResponse(null, false, 'This operation is not permitted!');
        // }

        $newPassword = Str::random(8);
        $user->update(['password' => Hash::make($newPassword)]);
        $emailBody = $this->emailTemplateService->newUserTemplate($user->first_name, $user->company_email, $newPassword);
        $this->emailService->send($user->company_email, 'Invitation to Our System', $emailBody);

        return $this->successResponse(null, true, 'Invitation sent successfully.');
    }

    public function resetPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        // if ($user->id === 1) {
        //     return $this->successResponse(null, false, 'This operation is not permitted!');
        // }

        $user->update([
            'is_logout' => true,
            'password' => Hash::make($request->new_password),
        ]);

        $emailBody = $this->emailTemplateService->passwordChangedTemplate($user->first_name, $user->company_email, $request->new_password);
        $this->emailService->send($user->company_email, 'Password Changed Successfully', $emailBody);

        return $this->successResponse(null, true, 'Password changed successfully.');
    }


    public function active($id)
    {
        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        // if ($user->id === 1) {
        //     return $this->successResponse(null, false, 'This operation is not permitted!');
        // }

        $user->update([
            'is_active' => !$user->is_active,
            'update_user_id' => Auth::id(),
            'update_date' => now(),
        ]);

        if (!$user->is_active) {
            $user->update(['is_logout' => true]);
        }

        $action = $user->is_active ? 'activated' : 'deactivated';

        return $this->successResponse(null, true, "User {$action} successfully.");
    }

    public function delete($id)
    {
        $user = User::where('id', $id)->where('is_delete', 0)->first();

        if (!$user) {
            return $this->successResponse(null, false, 'User does not exist!');
        }

        if ($user->id === 1) {
            return $this->successResponse(null, false, 'This operation is not permitted!');
        }

        $user->update([
            'is_logout' => true,
            'is_delete' => 1,
            'delete_user_id' => Auth::id(),
            'delete_date' => now(),
        ]);

        return $this->successResponse(null, true, 'User deleted successfully.');
    }


    public function refresh()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->is_active || $user->is_logout || $user->is_delete == 1) {
            return $this->successResponse(null, false, 'You have been logged out. Please log in again to continue!');
        }

        $permissionIds = RolePermission::where('role_id', $user->role_id)
            ->pluck('permission_id');

        $permissions = Permission::whereIn('id', $permissionIds)
            ->pluck('code')
            ->toArray();

        // Use the 'api' guard to refresh the token
        $token = Auth::guard('api')->refresh();

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'personal_email' => $user->personal_email,
            'company_email' => $user->company_email,
            'phone' => $user->phone,
            'address' => $user->address,
            'image' => $user->image ? asset($user->image) : asset('images/default-user.png'),
        ];

        return $this->successResponse([
            'user' => $userData,
            'token' => $token,
            'permissions' => $permissions,
        ], true, 'Data returned successfully.');
    }



    private function generateRandomString($length = 8)
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()'), 0, $length);
    }

}
