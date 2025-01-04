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

        $query = Role::where('is_delete', false);
        if (!empty($q)) {
            $query->where('name', 'like', '%' . strtolower($q) . '%');
        }

        $vm = [
            'total_count' => $query->count(),
            'data' => $query->skip(($page - 1) * $size)->take($size)->get()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'insert_user' => User::where('id', $role->insert_user_id)->first(['id', 'first_name', 'last_name', 'image']),
                    'insert_date' => $role->insert_date,
                    'update_date' => $role->update_date,
                    'num_of_members' => User::where('role_id', $role->id)->count(),
                ];
            }),
        ];

        return $this->successResponse($vm, true, 'Data returned successfully.');
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
