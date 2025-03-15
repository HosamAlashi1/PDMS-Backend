<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class CommonController extends Controller
{

    public function permissions()
    {
        $parentPermissions = Permission::where('parent_id', null)->orderBy('order')->get();
        $allPermissions = Permission::where('parent_id', '!=', null)->orderBy('order')->get();

        $result = $parentPermissions->map(function ($parent) use ($allPermissions) {
            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'code' => $parent->code,
                'parent_id' => $parent->parent_id,
                'sub_permissions' => $allPermissions->filter(function ($child) use ($parent) {
                    return $child->parent_id == $parent->id;
                })->values()->transform(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'code' => $child->code
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data returned successfully.',
            'data' => $result
        ]);
    }

    public function lookups()
    {
        $roles = Role::where('is_delete', false)->get();

        return response()->json([
            'success' => true,
            'message' => 'Data returned successfully.',
            'data' => ['roles' => $roles]
        ]);
    }
}
