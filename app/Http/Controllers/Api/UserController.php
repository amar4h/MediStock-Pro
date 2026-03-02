<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List users for the current tenant (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('role')
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->whereHas('role', fn ($r) => $r->where('name', $request->input('role')));
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(function ($query) use ($term) {
                    $query->where('name', 'like', "%{$term}%")
                          ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Create a new user for the current tenant.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => new UserResource($user->load('role')),
        ], 201);
    }

    /**
     * Get a specific user's details.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('role.permissions')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * Update a user.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => new UserResource($user->fresh()->load('role')),
        ]);
    }

    /**
     * Soft delete (deactivate) a user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deactivated successfully.',
        ]);
    }
}
