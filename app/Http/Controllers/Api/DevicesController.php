<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponseTrait;
use App\Enums\DevicesStatus;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class DevicesController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('role:DEVICES');
        $this->middleware('role:VIEW_DEVICES')->only('list');
        $this->middleware('role:ADD_DEVICE')->only('add');
        $this->middleware('role:EDIT_DEVICE')->only('edit');
        $this->middleware('role:DELETE_DEVICE')->only('delete');
        $this->middleware('role:IMPORT_DEVICES')->only('import');
    }

    public function list(Request $request)
    {
        $query = Device::query();

        if ($request->has('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('ip_address', 'like', '%' . $request->q . '%')
                    ->orWhere('line_code', 'like', '%' . $request->q . '%')
                    ->orWhere('latitude', 'like', '%' . $request->q . '%')
                    ->orWhere('longitude', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $devices = $query->orderByDesc('created_at')
            ->paginate($request->size ?? 10);

        $devices->transform(function ($device) {
            return [
                'id' => $device->id,
                'name' => $device->name,
                'ip_address' => $device->ip_address,
                'line_code' => $device->line_code,
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
                'device_type' => $device->device_type,
                'status' => $device->status,
                'response_time' => $device->response_time,
                'offline_since' => $device->offline_since,
                'downtime' => ($device->status === DevicesStatus::OfflineShortTerm->value || $device->status === DevicesStatus::OfflineLongTerm->value) && $device->offline_since
                    ? $this->formatDowntime(now()->diff($device->offline_since))
                    : '-',
            ];
        });

        $response = [
            'data' => $devices->items(),
            'total_records' => $devices->total(),
            'total_count' => $devices->count(),
            'current_page' => $devices->currentPage(),
            'per_page' => $devices->perPage(),
        ];

        return $this->successResponse($response, true, 'Data returned successfully.');
    }


    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'line_code' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_type' => 'required|string|max:255',
        ]);

        if (Device::where('name', $request->name)->orWhere('ip_address', $request->ip_address)->orWhere('line_code', $request->line_code)->exists()) {
            return $this->successResponse(null, false, 'Device already exists!');
        }

        Device::create([
            'name' => $request->name,
            'ip_address' => $request->ip_address,
            'line_code' => $request->line_code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'device_type' => $request->device_type,
            'status' => DevicesStatus::Online->value,
            'insert_user_id' => Auth::id(),
            'insert_date' => now(),
            'update_date' => now(),
        ]);

        return $this->successResponse(null, true, 'Device added successfully.');
    }

    public function edit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'line_code' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'device_type' => 'required|string|max:255',
        ]);

        $device = Device::find($id);

        if (!$device) {
            return $this->successResponse(null, false, 'Device not found!');
        }

        if (Device::where('id', '!=', $id)
            ->where(function ($query) use ($request) {
                $query->where('name', $request->name)
                    ->orWhere('ip_address', $request->ip_address)
                    ->orWhere('line_code', $request->line_code);
            })
            ->exists()) {
            return $this->successResponse(null, false, 'Device already exists!');
        }

        $device->update([
            'name' => $request->name,
            'ip_address' => $request->ip_address,
            'line_code' => $request->line_code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'device_type' => $request->device_type,
            'update_user_id' => Auth::id(),
            'update_date' => now(),
        ]);

        return $this->successResponse(null, true, 'Device updated successfully.');
    }

    public function delete($id)
    {
        $device = Device::find($id);

        if (!$device) {
            return $this->successResponse(null, false, 'Device not found!');
        }

        $device->delete();

        return $this->successResponse(null, true, 'Device deleted successfully.');
    }

    private function formatDowntime($duration)
    {
        if ($duration->days > 0) {
            return "{$duration->days} d, {$duration->h} h";
        }

        if ($duration->h > 0) {
            return "{$duration->h} h, {$duration->i} m";
        }

        return "{$duration->i} m";
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null, false, $validator->errors());
        }

        $file = $request->file('file');

        try {
            $data = Excel::toArray([], $file);
            $worksheet = $data[0];

            // Skip the header row
            $devicesToAdd = [];
            $devicesToUpdate = [];

            foreach ($worksheet as $index => $row) {
                if ($index === 0) {
                    continue; // Skip header row
                }

                $line_code = $row[0] ?? null;
                $name = $row[1] ?? null;
                $ip_address = $row[2] ?? null;
                $latitudeText = $row[3] ?? null;
                $longitudeText = $row[4] ?? null;
                $device_type = strtolower(str_replace(" ", "_", $row[5] ?? ''));

                if (empty($line_code)) {
                    return $this->successResponse(null, false, "Line Code is missing or empty at row " . ($index + 1));
                }
                if (empty($name)) {
                    return $this->successResponse(null, false, "Device Name is missing or empty at row " . ($index + 1));
                }
                if (empty($ip_address)) {
                    return $this->successResponse(null, false, "IP Address is missing or empty at row " . ($index + 1));
                }
                if (!is_numeric($latitudeText)) {
                    return $this->successResponse(null, false, "Invalid Latitude at row " . ($index + 1));
                }
                if (!is_numeric($longitudeText)) {
                    return $this->successResponse(null, false, "Invalid Longitude at row " . ($index + 1));
                }
                if (empty($device_type)) {
                    return $this->successResponse(null, false, "Device Type is missing or invalid at row " . ($index + 1));
                }

                $latitude = (float)$latitudeText;
                $longitude = (float)$longitudeText;

                $existingDevice = Device::where('ip_address', $ip_address)->first();

                if ($existingDevice) {
                    $existingDevice->name = $name;
                    $existingDevice->line_code = $line_code;
                    $existingDevice->latitude = $latitude;
                    $existingDevice->longitude = $longitude;
                    $existingDevice->device_type = $device_type;
                    $existingDevice->update_user_id = Auth::id();
                    $existingDevice->update_date = now();
                    $devicesToUpdate[] = $existingDevice;
                } else {
                    $devicesToAdd[] = [
                        'name' => $name,
                        'ip_address' => $ip_address,
                        'line_code' => $line_code,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'device_type' => $device_type,
                        'status' => DevicesStatus::Online->value,
                        'offline_since' => null,
                        'insert_user_id' => Auth::id(),
                        'insert_date' => now(),
                        'update_date' => now(),
                    ];
                }
            }

            if (!empty($devicesToAdd)) {
                Device::insert($devicesToAdd); // Insert array of associative arrays
            }

            if (!empty($devicesToUpdate)) {
                foreach ($devicesToUpdate as $device) {
                    $device->save(); // Save updated devices individually
                }
            }

            return $this->successResponse(null, true, "File imported successfully.");
        } catch (\Exception $e) {
            return $this->successResponse(null, false, "An error occurred while processing the file: " . $e->getMessage());
        }
    }

}


