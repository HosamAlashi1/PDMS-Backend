<?php

namespace App\Traits;
use Illuminate\Support\Facades\Auth;
trait ApiResponseTrait
{

    public function successResponse($data, $success = true, $message = 'msg')
    {
        return response()->json([
            'data' => $data,
            'success' => $success,
            'message' => $message,
        ], 200);
    }

    /**
     * Error response helper.
     *
     * @param int $status
     * @param mixed $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($status = 400, $message = 'error')
    {
        return response()->json([
            'data' => null,
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function uploadImage($folder,$image){
        $image->store('/',$folder);
        $filename = $image->hashName();
        $path = 'images/' . $filename;
        return $path;
    }


    protected function createNewToken($token)
    {
        return $this->successResponse([
            'access_token' => $token,
            'user' => auth()->user(),
        ], true);
    }





}
