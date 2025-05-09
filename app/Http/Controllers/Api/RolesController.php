<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponseTrait;

class RolesController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('role:ROLES');
        $this->middleware('role:VIEW_ROLES')->only('list');
        $this->middleware('role:EDIT_ROLE')->only(['details', 'edit']);
        $this->middleware('role:ADD_ROLE')->only('add');
        $this->middleware('role:DELETE_ROLE')->only('delete');
    }

    public function list(Request $request)
    {
        $q = $request->input('q', '');
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);

        // Build the base query
        $query = Role::where('is_delete', false);

        // Apply search filter if provided
        if (!empty($q)) {
            $query->where('name', 'like', '%' . strtolower($q) . '%');
        }

        // Calculate total count before pagination
        $totalCount = $query->count();

        // Paginate the results
        $roles = $query->skip(($page - 1) * $size)->take($size)->get();

        // Map the roles with required details
        $data = $roles->map(function ($role) {
            $user = $role->insert_user_id ? User::find($role->insert_user_id, ['id', 'first_name', 'last_name', 'image']) : null;

            return [
                'id' => $role->id,
                'name' => $role->name,
                'insert_user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name, // Combining first name and last name
                    'image' => $user->image
                ] : null,
                'insert_date' => $role->created_at,
                'update_date' => $role->updated_at,
                'num_of_members' => User::where('role_id', $role->id)->count(),
            ];
        });

        // Prepare the response
        $response = [
            'data' => $data,
            'total_records' => $totalCount,
            'current_page' => $page,
            'per_page' => $size,
        ];

        return $this->successResponse($response, true, 'Data returned successfully.');
    }

    public function details($id)
    {
        $role = Role::where('id', $id)->where('is_delete', false)->first();

        if (!$role) {
            return $this->successResponse(null, false, 'Role does not exist!');
        }

        if ($role->id == 1) {
            return $this->successResponse(null, false, 'This operation is not permitted!');
        }

        $permissionIds = RolePermission::where('role_id', $id)->pluck('permission_id');
        $permissionsList = Permission::whereIn('id', $permissionIds)->get();

        $parentPermissions = $permissionsList->where('parent_id', 0);
        $allPermissions = $permissionsList->where('parent_id', '!=', 0);

        $permissions = $parentPermissions->map(function ($parent) use ($allPermissions) {
            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'code' => $parent->code,
                'parent_id' => $parent->parent_id,
                'sub_permissions' => $allPermissions->where('parent_id', $parent->id)->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'code' => $child->code,
                    ];
                })->values(),
            ];
        });

        return $this->successResponse(['role' => $role, 'permissions' => $permissions], true, 'Data returned successfully.');
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissionsIds' => 'required|array',
        ]);

        if (Role::where('name', $request->name)->where('is_delete', false)->exists()) {
            return $this->successResponse(null, false, 'Role already exists!');
        }

        $newRole = Role::create([
            'name' => $request->name,
            'is_delete' => false,
            'insert_user_id' => Auth::id(),
            'insert_date' => now(),
            'update_date' => now(),
        ]);

        $rolePermissions = collect($request->permissionsIds)->map(function ($permissionId) use ($newRole) {
            return [
                'role_id' => $newRole->id,
                'permission_id' => $permissionId,
            ];
        });

        RolePermission::insert($rolePermissions->toArray());

        return $this->successResponse(null, true, 'Role added successfully.');
    }

    public function edit($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissionsIds' => 'required|array',
        ]);

        $role = Role::where('id', $id)->where('is_delete', false)->first();

        if (!$role) {
            return $this->successResponse(null, false, 'Role does not exist!');
        }

        if ($role->id == 1) {
            return $this->successResponse(null, false, 'This operation is not permitted!');
        }

        $role->update([
            'name' => $request->name,
            'update_user_id' => Auth::id(),
            'update_date' => now(),
        ]);

        RolePermission::where('role_id', $role->id)->delete();

        $rolePermissions = collect($request->permissionsIds)->map(function ($permissionId) use ($role) {
            return [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ];
        });

        RolePermission::insert($rolePermissions->toArray());

        return $this->successResponse(null, true, 'Role updated successfully.');
    }

    public function delete($id)
    {
        $role = Role::where('id', $id)->where('is_delete', false)->first();

        if (!$role) {
            return $this->successResponse(null, false, 'Role does not exist!');
        }

        if ($role->id == 1) {
            return $this->successResponse(null, false, 'This operation is not permitted!');
        }

        if (User::where('role_id', $id)->exists()) {
            return $this->successResponse(null, false, 'Role is already in use so it cannot be deleted!');
        }

        $role->update([
            'is_delete' => true,
            'delete_user_id' => Auth::id(),
            'delete_date' => now(),
        ]);

        return $this->successResponse(null, true, 'Role deleted successfully.');
    }
}
