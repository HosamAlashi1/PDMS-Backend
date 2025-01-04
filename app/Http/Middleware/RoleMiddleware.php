<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $requiredPermission)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $user = Auth::user();

        // Check if user has a role
        if (!$user->role) {
            return response()->json(['success' => false, 'message' => 'Forbidden. Role not assigned.'], 403);
        }

        // Get all permissions assigned to the user's role
        $rolePermissions = Permission::whereIn(
            'id',
            $user->role->permissions()->pluck('permissions.id')
        )->pluck('code')->toArray();

        // Check if the required permission exists in the user's role permissions
        if (!in_array($requiredPermission, $rolePermissions)) {
            return response()->json(['success' => false, 'message' => 'Forbidden. You do not have access to this resource.'], 403);
        }

        return $next($request);
    }
}
