<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantSettingController extends Controller
{
    /**
     * Get the current tenant's settings and profile.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        return response()->json([
            'success' => true,
            'data'    => $tenant,
        ]);
    }

    /**
     * Update the current tenant's settings and profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'owner_name'      => 'sometimes|string|max:255',
            'phone'           => 'sometimes|string|max:20',
            'drug_license_no' => 'nullable|string|max:100',
            'gstin'           => 'nullable|string|max:20',
            'address_line1'   => 'nullable|string|max:255',
            'address_line2'   => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'pincode'         => 'nullable|string|max:10',
            'settings'        => 'nullable|array',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tenant settings updated successfully.',
            'data'    => $tenant->fresh(),
        ]);
    }
}
