<?php

namespace App\Http\Controllers\Api;

use App\Enums\DevicesStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('role:DASHBOARD');
        $this->middleware('role:VIEW_STATISTICS')->only('statistics');
        $this->middleware('role:VIEW_STATISTICS_BY_MONTH')->only('detailedStatisticsByMonth');
    }

    /**
     * Get general statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {

        $result = [
            'total_count' => Device::count(),
            'online_count' => Device::where('status', DevicesStatus::Online)->count(),
            'offline_short_term_count' => Device::where('status', DevicesStatus::OfflineShortTerm)->count(),
            'offline_long_term_count' => Device::where('status', DevicesStatus::OfflineLongTerm)->count(),
        ];

        return $this->successResponse($result, true, 'Data returned successfully.');
    }

    /**
     * Get detailed statistics by month
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailedStatisticsByMonth()
    {
        $statistics = [];
        $currentDate = Carbon::now();

        for ($i = 0; $i < 6; $i++) {
            $startDate = $currentDate->copy()->startOfMonth()->subMonths($i);
            $endDate = $startDate->copy()->endOfMonth();

            $devicesInMonth = Device::where('insert_date', '<=', $endDate)->get();

            $stableDevices = $devicesInMonth->filter(function ($device) {
                return $device->status === DevicesStatus::Online ||
                    ($device->offline_since !== null && now()->diffInHours($device->offline_since) < 24);
            })->count();

            $unstableDevices = $devicesInMonth->count() - $stableDevices;

            $statistics[] = [
                'month' => $startDate->format('F Y'),
                'stable_devices' => $stableDevices,
                'unstable_devices' => $unstableDevices,
            ];
        }

        $statistics = array_reverse($statistics);

        return $this->successResponse($statistics, true, 'Data returned successfully.');
    }
}
