<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): View
    {
        $tenant = auth()->user()->tenant;
        $users = User::forTenant()
            ->with('role:id,name')
            ->orderBy('name')
            ->get();
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('pages.settings.index', compact('tenant', 'users', 'roles'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name'     => 'required|string|max:255',
            'address'        => 'nullable|string|max:1000',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:255',
            'gstin'          => 'nullable|string|max:15',
            'drug_license'   => 'nullable|string|max:100',
        ]);

        $tenant = auth()->user()->tenant;
        $tenant->update([
            'name'            => $validated['store_name'],
            'address_line1'   => $validated['address'] ?? null,
            'phone'           => $validated['phone'] ?? null,
            'email'           => $validated['email'] ?? null,
            'gstin'           => $validated['gstin'] ?? null,
            'drug_license_no' => $validated['drug_license'] ?? null,
        ]);

        return redirect()->route('settings.index')->with('success', 'Settings updated.');
    }

    public function storeUser(StoreUserRequest $request): RedirectResponse
    {
        User::create(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()->tenant_id]
        ));

        return redirect()->route('settings.index')->with('success', 'User created successfully.');
    }
}
