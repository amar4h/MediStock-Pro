<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order:
     *  1. PermissionSeeder  - global permissions (not tenant-scoped)
     *  2. DefaultRoleSeeder - creates roles for any existing tenants
     *  3. DemoTenantSeeder  - optional demo data (controlled by APP_ENV or flag)
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DefaultRoleSeeder::class,
        ]);

        // Only seed demo data in local/testing environments or when explicitly requested.
        if ($this->shouldSeedDemo()) {
            $this->call(DemoTenantSeeder::class);
        }
    }

    /**
     * Determine whether the demo tenant should be seeded.
     *
     * The demo seeder runs when:
     * - APP_ENV is "local" or "testing", OR
     * - The --class option targets DemoTenantSeeder directly, OR
     * - The SEED_DEMO environment variable is truthy.
     */
    private function shouldSeedDemo(): bool
    {
        $env = app()->environment();

        if (in_array($env, ['local', 'testing'], true)) {
            return true;
        }

        if (env('SEED_DEMO', false)) {
            return true;
        }

        return false;
    }
}
