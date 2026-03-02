<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * List all roles for the current tenant.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $roles,
        ]);
    }
}
