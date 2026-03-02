<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions grouped by module.
     *
     * These are global (not tenant-scoped) and shared across the platform.
     * Roles (which ARE tenant-scoped) reference these permissions via the
     * role_permissions pivot table.
     *
     * @return array<string, list<string>>
     */
    public static function permissionMatrix(): array
    {
        return [
            'items' => [
                'items.view',
                'items.create',
                'items.update',
                'items.delete',
            ],

            'categories' => [
                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',
            ],

            'manufacturers' => [
                'manufacturers.view',
                'manufacturers.create',
                'manufacturers.update',
                'manufacturers.delete',
            ],

            'purchases' => [
                'purchases.view',
                'purchases.create',
                'purchases.update',
                'purchases.return',
                'purchases.scan_invoice',
            ],

            'sales' => [
                'sales.view',
                'sales.create',
                'sales.return',
                'sales.void',
            ],

            'inventory' => [
                'inventory.view',
                'inventory.discard',
                'inventory.adjust',
            ],

            'customers' => [
                'customers.view',
                'customers.create',
                'customers.update',
                'customers.delete',
                'customers.payments',
            ],

            'suppliers' => [
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'suppliers.delete',
                'suppliers.payments',
            ],

            'expenses' => [
                'expenses.view',
                'expenses.create',
                'expenses.update',
                'expenses.delete',
            ],

            'reports' => [
                'reports.sales',
                'reports.profit',
                'reports.gst',
                'reports.expenses',
            ],

            'dashboard' => [
                'dashboard.view',
            ],

            'users' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
            ],

            'settings' => [
                'settings.view',
                'settings.update',
            ],
        ];
    }

    /**
     * Seed all permissions.
     */
    public function run(): void
    {
        $matrix = self::permissionMatrix();

        foreach ($matrix as $module => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::updateOrCreate(
                    ['name' => $permissionName],
                    [
                        'module'     => $module,
                        'guard_name' => 'web',
                    ],
                );
            }
        }

        $this->command->info('Permissions seeded: ' . collect($matrix)->flatten()->count() . ' permissions across ' . count($matrix) . ' modules.');
    }
}
