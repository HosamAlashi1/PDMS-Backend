<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('role:SETTINGS');
        $this->middleware('role:VIEW_SETTINGS');
        $this->middleware('role:EDIT_SETTINGS');

    }

    /**
     * List all settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $settings = Setting::all();
        return $this->successResponse($settings, true, 'Data returned successfully.');
    }

    /**
     * Edit settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_phone' => 'required|string|max:20',
            'company_address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, "Validation failed. Please ensure all fields are properly filled.");
        }

        try {
            // Update settings
            Setting::where('id', 1)->update(['value' => $request->input('company_name')]);
            Setting::where('id', 2)->update(['value' => $request->input('company_email')]);
            Setting::where('id', 3)->update(['value' => $request->input('company_phone')]);
            Setting::where('id', 4)->update(['value' => $request->input('company_address')]);

            return $this->successResponse(null, true, 'Settings updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(500, 'Failed to update settings.');
        }
    }
}
