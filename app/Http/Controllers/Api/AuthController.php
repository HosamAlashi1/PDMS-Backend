<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$token = JWTAuth::attempt($validator->validated())) {
            return $this->errorResponse(401, 'Invalid email or password');
        }

        if (!$user->is_active) {
            return $this->errorResponse(403, 'Account is inactive. Please contact support.');
        }

        return $this->createNewToken($token);



    }

    public function logout()
    {
        auth()->logout();
        return $this->apiResponse(['message' => 'Logged out successfully'],200);
    }
}




