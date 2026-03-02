<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Sequence;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * Default roles created for every new tenant.
     */
    private const DEFAULT_ROLES = ['owner', 'store_manager', 'pharmacist', 'cashier'];

    /**
     * Default invoice sequences created for every new tenant.
     */
    private const DEFAULT_SEQUENCES = [
        ['type' => 'sale', 'prefix' => 'INV-'],
        ['type' => 'sale_return', 'prefix' => 'SR-'],
        ['type' => 'purchase_return', 'prefix' => 'PR-'],
    ];

    /**
     * Permissions assigned to each role by default.
     * The owner role gets ALL permissions (bypasses checks), so not listed here.
     */
    private const ROLE_PERMISSIONS = [
        'store_manager' => [
            'items.view', 'items.create', 'items.edit', 'items.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'manufacturers.view', 'manufacturers.create', 'manufacturers.edit', 'manufacturers.delete',
            'batches.view',
            'suppliers.view', 'suppliers.create', 'suppliers.edit',
            'purchases.view', 'purchases.create', 'purchases.edit',
            'purchase_returns.view', 'purchase_returns.create',
            'customers.view', 'customers.create', 'customers.edit',
            'sales.view', 'sales.create',
            'sale_returns.view', 'sale_returns.create',
            'customer_payments.view', 'customer_payments.create',
            'supplier_payments.view', 'supplier_payments.create',
            'inventory.view', 'inventory.discard',
            'expenses.view', 'expenses.create', 'expenses.edit',
            'expense_categories.view', 'expense_categories.create', 'expense_categories.edit',
            'reports.view',
            'dashboard.view',
            'users.view',
            'invoice_scans.view', 'invoice_scans.create',
        ],
        'pharmacist' => [
            'items.view', 'items.create', 'items.edit',
            'categories.view',
            'manufacturers.view',
            'batches.view',
            'suppliers.view',
            'purchases.view', 'purchases.create',
            'customers.view', 'customers.create',
            'sales.view', 'sales.create',
            'sale_returns.view', 'sale_returns.create',
            'customer_payments.view', 'customer_payments.create',
            'inventory.view',
            'dashboard.view',
            'invoice_scans.view', 'invoice_scans.create',
        ],
        'cashier' => [
            'items.view',
            'batches.view',
            'customers.view', 'customers.create',
            'sales.view', 'sales.create',
            'customer_payments.view', 'customer_payments.create',
            'dashboard.view',
        ],
    ];

    /**
     * Create a new tenant with owner user, default roles, and default sequences.
     *
     * @param  array  $data  Expected keys: name, email, phone, password, owner_name,
     *                       and optional: drug_license_no, gstin, address_line1,
     *                       address_line2, city, state, pincode
     * @return array{tenant: Tenant, user: User}
     */
    public function createTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the tenant
            $tenant = Tenant::create([
                'name'                => $data['name'],
                'slug'                => Str::slug($data['name']) . '-' . Str::lower(Str::random(6)),
                'owner_name'          => $data['owner_name'],
                'email'               => $data['email'],
                'phone'               => $data['phone'],
                'drug_license_no'     => $data['drug_license_no'] ?? null,
                'gstin'               => $data['gstin'] ?? null,
                'address_line1'       => $data['address_line1'] ?? null,
                'address_line2'       => $data['address_line2'] ?? null,
                'city'                => $data['city'] ?? null,
                'state'               => $data['state'] ?? 'Maharashtra',
                'pincode'             => $data['pincode'] ?? null,
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(14),
                'settings'            => $this->defaultSettings(),
            ]);

            // 2. Create default roles for the tenant
            $roles = [];
            foreach (self::DEFAULT_ROLES as $roleName) {
                $roles[$roleName] = Role::create([
                    'tenant_id'  => $tenant->id,
                    'name'       => $roleName,
                    'guard_name' => 'web',
                ]);
            }

            // 3. Assign permissions to roles
            $this->assignDefaultPermissions($roles);

            // 4. Create the owner user
            $ownerUser = User::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'role_id'   => $roles['owner']->id,
                'name'      => $data['owner_name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // 5. Seed default invoice sequences
            foreach (self::DEFAULT_SEQUENCES as $seq) {
                Sequence::withoutGlobalScopes()->create([
                    'tenant_id'   => $tenant->id,
                    'type'        => $seq['type'],
                    'prefix'      => $seq['prefix'],
                    'next_number' => 1,
                ]);
            }

            return [
                'tenant' => $tenant,
                'user'   => $ownerUser,
            ];
        });
    }

    /**
     * Update tenant settings (merges with existing settings).
     */
    public function updateSettings(Tenant $tenant, array $settings): Tenant
    {
        $currentSettings = $tenant->settings ?? [];
        $mergedSettings = array_merge($currentSettings, $settings);

        $tenant->update(['settings' => $mergedSettings]);

        return $tenant->fresh();
    }

    /**
     * Assign default permissions to the created roles.
     *
     * @param  array<string, Role>  $roles
     */
    private function assignDefaultPermissions(array $roles): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            if (! isset($roles[$roleName])) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $permissionNames)
                ->pluck('id')
                ->toArray();

            if (! empty($permissionIds)) {
                $roles[$roleName]->permissions()->attach($permissionIds);
            }
        }
    }

    /**
     * Default tenant settings.
     */
    private function defaultSettings(): array
    {
        return [
            'currency'              => 'INR',
            'date_format'           => 'd/m/Y',
            'low_stock_threshold'   => 10,
            'near_expiry_days'      => 30,
            'invoice_prefix'        => 'INV-',
            'gst_enabled'           => true,
            'print_header'          => null,
            'print_footer'          => null,
            'enable_invoice_scan'   => false,
        ];
    }
}
