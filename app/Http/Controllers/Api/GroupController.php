<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    use ApiResponseTrait;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:groups,title',
            'color' => 'required|string|max:7',
            'coordinates' => 'required|array',
            'city' => 'required|string|max:255',
            'governorate' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null,false, 'Validation errors', $validator->errors());
        }

        try {
            $group = Group::create([
                'title' => $request->input('title'),
                'color' => $request->input('color'),
                'coordinates' => json_encode($request->input('coordinates')),
                'city' => $request->input('city'),
                'governorate' => $request->input('governorate'),
                'is_active' => true,
                'is_delete' => false,
                'insert_user_id' => auth()->id(),
                'insert_user_name' => auth()->user()->name ?? 'System',
                'insert_date' => now(),
            ]);

            return $this->successResponse($group, true, 'Group has been added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(500, 'An error occurred', $e->getMessage());
        }
    }

    public function list(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $size = $request->input('size', 10);
            $page = $request->input('page', 1);
            $skip = ($page - 1) * $size;

            // Fetch paginated groups
            $groups = Group::where('title', 'LIKE', "%$query%")->skip($skip)->take($size)->get();
            $totalGroups = Group::where('title', 'LIKE', "%$query%")->count();
            $totalPages = ceil($totalGroups / $size);

            $response = [
                'data' => $groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'title' => $group->title,
                        'description' => $group->description, // Assuming a `description` field exists
                        'created_at' => $group->created_at,
                        'updated_at' => $group->updated_at,
                    ];
                }),
                'current_page' => $page,
                'total_pages' => $totalPages,
                'size' => $size,
                'total_records' => $totalGroups,
                'total_count' => $groups->count(),
            ];

            return $this->successResponse($response, true, 'Groups fetched successfully');
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }


    public function all()
    {
        try {
            $groups = Group::all();
            return $this->successResponse($groups, true, 'Data returned successfully');
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }

    public function activate($id, Request $request)
    {
        try {
            $group = Group::find($id);
            if (!$group) {
                return $this->successResponse(null,false, 'Group not found');
            }

            // Cast the value to an integer
            $value = filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $group->is_active = $value;
            $group->save();

            $message = $group->is_active ? 'activated' : 'deactivated';
            return $this->successResponse(null, false, "Group has been $message successfully");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }


    public function update($id, Request $request)
    {
        try {
            $group = Group::find($id);
            if (!$group) {
                return $this->successResponse(null,false, 'Group not found');
            }

            $validated = $request->validate([
                'title' => 'required|string',
            ]);

            $group->title = $validated['title'];
            $group->save();

            return $this->successResponse($group, true, 'Group has been updated successfully');
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $group = Group::find($id);
            if (!$group) {
                return $this->successResponse(null,false, 'Group not found');
            }

            $group->delete();

            return $this->successResponse(null, true, 'Group has been deleted successfully');
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }

}
