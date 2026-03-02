<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * Handle login form submission.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors([
                'email' => 'Your account has been deactivated. Contact your administrator.',
            ])->onlyInput('email');
        }

        $user->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle registration form submission.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name'      => 'required|string|max:255',
            'owner_name'      => 'required|string|max:255',
            'email'           => 'required|email|unique:tenants,email',
            'phone'           => 'required|string|max:20',
            'password'        => ['required', 'string', 'confirmed', Password::min(8)],
            'drug_license_no' => 'nullable|string|max:100',
            'gstin'           => 'nullable|string|max:20',
        ]);

        $result = $this->tenantService->createTenantWithOwner($validated);

        Auth::login($result['user']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
