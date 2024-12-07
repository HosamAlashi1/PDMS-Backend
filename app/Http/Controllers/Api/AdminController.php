<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);

        $skip = ($page - 1) * $size;

        $admins = User::where('name', 'LIKE', "%$query%")->skip($skip)->take($size)->get();
        $totalAdmins = User::where('name', 'LIKE', "%$query%")->count();


        $totalPages = ceil($totalAdmins / $size);

        return $this->successResponse([
            'admins' => $admins,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'size' => $size,
            'total_count' => $totalAdmins,
        ], true, 'Admins fetched successfully');
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15|unique:users,phone',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null,false, $validator->errors());
        }

        // Image upload logic
        $imagePath = '';
        if ($request->has('image')) {
            $imagePath = $this->uploadImage('upload', $request->image);
        }

        // Create the new admin user
        $admin = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'image' => $imagePath,
        ]);

        return $this->successResponse([
            'admin' => $admin,
        ], true, 'Admin created successfully');
    }

    public function activate($id, Request $request)
    {
        try {
            $admin = User::find($id);
            if (!$admin) {
                return $this->successResponse(null,false, 'Admin not found');
            }

            // Cast the value to an integer
            $value = filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $admin->is_active = $value;
            $admin->save();

            $message = $admin->is_active ? 'activated' : 'deactivated';
            return $this->successResponse(null, true, "$admin->name has been $message successfully");
        } catch (\Exception $ex) {
            return $this->errorResponse(400, 'An error occurred', $ex->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:10|max:15',
            'email' => 'required|email|unique:users,email,' . $id . '|max:255',
            'password' => 'nullable|string|min:6',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->successResponse(null,false, $validator->errors());
        }

        $admin = User::find($id);

        if (!$admin) {
            return $this->successResponse(null,false, 'Admin not found');
        }

        $data = $request->only(['name', 'phone', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            if ($admin->image) {
                Storage::disk('public')->delete($admin->image);
            }

            $data['image'] = $this->uploadImage('upload', $request->file('image'));
        }

        $admin->update($data);

        return $this->successResponse([
            'admin' => $admin,
        ], false, 'Admin updated successfully');
    }


    public function destroy($id)
    {
        $admin = User::find($id);
        if (!$admin) {
            return $this->successResponse(null,false, 'Admin not found');
        }

        if ($admin->id === auth()->id()) {
            return $this->successResponse(null,false, 'You cannot delete yourself');
        }

        if ($admin->image) {
            Storage::disk('public')->delete($admin->image);
        }

        $admin->delete();

        return $this->successResponse(null, true, 'Admin deleted successfully');
    }


}
