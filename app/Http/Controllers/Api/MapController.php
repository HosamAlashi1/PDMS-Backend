<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Traits\ApiResponseTrait;

class MapController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('role:MAP');
        $this->middleware('role:VIEW_MAP');
    }

    /**
     * List all devices with optional status filtering
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {

        $status = $request->query('status', 0);

        $query = Device::query(); // Device model for the devices table
        $totalCount = $query->count();

        if ($status > 0) {
            $query->where('status', $status);
        }

        $totalRecords = $query->count();

        $data = $query->select([
            'id',
            'line_code',
            'name',
            'ip_address',
            'latitude',
            'longitude',
            'device_type',
            'status',
        ])->get();

        $response = [
            'total_count' => $totalCount,
            'total_records' => $totalRecords,
            'data' => $data,
        ];

        return $this->successResponse($response, true, 'Data returned successfully.');
    }
}
