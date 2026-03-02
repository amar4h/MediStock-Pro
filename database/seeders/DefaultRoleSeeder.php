<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultRoleSeeder extends Seeder
{
    /**
     * The default role-to-permission mapping.
     *
     * This is a TEMPLATE seeder. The static rolePermissionMap() method is
     * designed to be called by TenantService when provisioning a new tenant,
     * so the same permission matrix is used everywhere.
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        // Collect every permission name from the PermissionSeeder matrix.
        $allPermissions = collect(PermissionSeeder::permissionMatrix())
            ->flatten()
            ->all();

        // Store Manager gets everything EXCEPT users.delete and settings.update.
        $storeManagerPermissions = array_values(
            array_diff($allPermissions, ['users.delete', 'settings.update'])
        );

        return [
            'Owner' => $allPermissions,

            'Store Manager' => $storeManagerPermissions,

            'Pharmacist' => [
                'items.view',
                'items.create',
                'items.update',
                'categories.view',
                'manufacturers.view',
                'purchases.view',
                'purchases.create',
                'purchases.scan_invoice',
                'sales.view',
                'sales.create',
                'sales.return',
                'inventory.view',
                'customers.view',
                'customers.create',
                'dashboard.view',
            ],

            'Cashier' => [
                'items.view',
                'sales.view',
                'sales.create',
                'sales.return',
                'customers.view',
                'customers.create',
                'customers.payments',
                'dashboard.view',
            ],
        ];
    }

    /**
     * Create the default roles and attach permissions for a specific tenant.
     *
     * Called by TenantService during tenant provisioning, or via this seeder
     * for existing tenants that need role bootstrapping.
     */
    public static function createRolesForTenant(Tenant $tenant): array
    {
        $map   = self::rolePermissionMap();
        $roles = [];

        foreach ($map as $roleName => $permissionNames) {
            // Create the role (tenant-scoped).
            $role = Role::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id'  => $tenant->id,
                    'name'       => $roleName,
                ],
                [
                    'guard_name' => 'web',
                ],
            );

            // Resolve permission IDs and sync them.
            $permissionIds = Permission::whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);

            $roles[$roleName] = $role;
        }

        return $roles;
    }

    /**
     * Run the seeder.
     *
     * When executed via `php artisan db:seed`, this creates default roles
     * for every existing tenant. For new tenants, TenantService should call
     * createRolesForTenant() directly.
     */
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Roles will be created when tenants are provisioned.');
            return;
        }

        foreach ($tenants as $tenant) {
            $roles = self::createRolesForTenant($tenant);
            $this->command->info("Created " . count($roles) . " roles for tenant: {$tenant->name}");
        }
    }
}
