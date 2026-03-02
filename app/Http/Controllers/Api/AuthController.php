<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * Register a new tenant and owner user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_name'      => 'required|string|max:255',
            'owner_name'      => 'required|string|max:255',
            'email'           => 'required|email|unique:tenants,email',
            'phone'           => 'required|string|max:20',
            'password'        => ['required', 'string', 'confirmed', Password::min(8)],
            'drug_license_no' => 'nullable|string|max:100',
            'gstin'           => 'nullable|string|max:20',
            'address_line1'   => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'pincode'         => 'nullable|string|max:10',
        ]);

        $result = $this->tenantService->createTenantWithOwner($validated);

        $token = $result['user']->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data'    => [
                'user'  => new UserResource($result['user']->load('role')),
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Login and return a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Contact your administrator.',
            ], 403);
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => new UserResource($user->load('role')),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout — revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user with tenant and role info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role.permissions', 'tenant']);

        return response()->json([
            'success' => true,
            'data'    => [
                'user'   => new UserResource($user),
                'tenant' => $user->tenant,
                'role'   => $user->role,
                'permissions' => $user->role?->permissions->pluck('name') ?? [],
            ],
        ]);
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update(['password' => $validated['password']]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    /**
     * Forgot password — send reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Delegate to Laravel's built-in password broker
        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'success' => $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT,
            'message' => __($status),
        ]);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
            }
        );

        return response()->json([
            'success' => $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET,
            'message' => __($status),
        ]);
    }
}
