<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Item;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    /**
     * Create a demo tenant with sample data for development and testing.
     */
    public function run(): void
    {
        // ── 1. Tenant ──────────────────────────────────────────────────

        $tenant = Tenant::updateOrCreate(
            ['slug' => 'demo'],
            [
                'name'                => 'Demo Medical Store',
                'slug'                => 'demo',
                'owner_name'          => 'Demo Owner',
                'email'               => 'demo@medistock.com',
                'phone'               => '9876543210',
                'drug_license_no'     => 'DL-DEMO-001',
                'gstin'               => '27AABCD1234E1Z5',
                'address_line1'       => '123 MG Road',
                'city'                => 'Mumbai',
                'state'               => 'Maharashtra',
                'pincode'             => '400001',
                'subscription_status' => 'trial',
                'trial_ends_at'       => Carbon::now()->addDays(30),
                'settings'            => [
                    'currency'        => 'INR',
                    'invoice_prefix'  => 'INV',
                    'low_stock_alert' => 10,
                ],
            ],
        );

        $this->command->info("Demo tenant created: {$tenant->name}");

        // ── 2. Roles & Permissions ─────────────────────────────────────

        $roles = DefaultRoleSeeder::createRolesForTenant($tenant);

        $this->command->info('Default roles created for demo tenant.');

        // ── 3. Owner User ──────────────────────────────────────────────

        $owner = User::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => 'demo@medistock.com',
            ],
            [
                'name'      => 'Demo Owner',
                'phone'     => '9876543210',
                'password'  => Hash::make('password123'),
                'role_id'   => $roles['Owner']->id,
                'is_active' => true,
            ],
        );

        $this->command->info("Demo owner user created: {$owner->email}");

        // ── 4. Categories ──────────────────────────────────────────────

        $categoryNames = ['Tablets', 'Capsules', 'Syrups', 'Injections', 'Medical Devices'];
        $categories    = [];

        foreach ($categoryNames as $name) {
            $categories[$name] = Category::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $name,
                ],
                ['is_active' => true],
            );
        }

        $this->command->info('Demo categories created: ' . count($categories));

        // ── 5. Manufacturers ───────────────────────────────────────────

        $manufacturerNames = ['Cipla', 'Sun Pharma', "Dr. Reddy's"];
        $manufacturers     = [];

        foreach ($manufacturerNames as $name) {
            $manufacturers[$name] = Manufacturer::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $name,
                ],
                ['is_active' => true],
            );
        }

        $this->command->info('Demo manufacturers created: ' . count($manufacturers));

        // ── 6. Items ───────────────────────────────────────────────────

        $itemDefinitions = $this->itemDefinitions($categories, $manufacturers, $tenant);
        $items           = [];

        foreach ($itemDefinitions as $def) {
            $items[] = Item::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $def['tenant_id'],
                    'name'      => $def['name'],
                ],
                $def,
            );
        }

        $this->command->info('Demo items created: ' . count($items));

        // ── 7. Batches (2 per item) ────────────────────────────────────

        $batchCount = 0;
        foreach ($items as $item) {
            $batches = $this->batchDefinitions($tenant, $item);
            foreach ($batches as $batchDef) {
                Batch::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id'    => $batchDef['tenant_id'],
                        'item_id'      => $batchDef['item_id'],
                        'batch_number' => $batchDef['batch_number'],
                    ],
                    $batchDef,
                );
                $batchCount++;
            }
        }

        $this->command->info("Demo batches created: {$batchCount}");

        // ── 8. Suppliers ───────────────────────────────────────────────

        $supplierDefinitions = [
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'ABC Distributors',
                'phone'           => '9988776655',
                'email'           => 'abc@distributors.com',
                'gstin'           => '27AABCA1234E1Z1',
                'drug_license_no' => 'DL-SUP-001',
                'address'         => '45 Industrial Area, Mumbai',
                'opening_balance' => 0.00,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'XYZ Pharma Wholesale',
                'phone'           => '9876501234',
                'email'           => 'xyz@pharmawholesale.com',
                'gstin'           => '27AABCX5678E1Z2',
                'drug_license_no' => 'DL-SUP-002',
                'address'         => '78 Pharma Market, Pune',
                'opening_balance' => 0.00,
                'is_active'       => true,
            ],
        ];

        foreach ($supplierDefinitions as $def) {
            Supplier::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $def['tenant_id'],
                    'name'      => $def['name'],
                ],
                $def,
            );
        }

        $this->command->info('Demo suppliers created: ' . count($supplierDefinitions));

        // ── 9. Customers ───────────────────────────────────────────────

        $customerDefinitions = [
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Walk-in Customer',
                'phone'           => null,
                'email'           => null,
                'address'         => null,
                'opening_balance' => 0.00,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Raj Kumar',
                'phone'           => '9876512345',
                'email'           => 'raj.kumar@email.com',
                'address'         => '56 Green Park, Mumbai',
                'opening_balance' => 0.00,
                'is_active'       => true,
            ],
        ];

        foreach ($customerDefinitions as $def) {
            Customer::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $def['tenant_id'],
                    'name'      => $def['name'],
                ],
                $def,
            );
        }

        $this->command->info('Demo customers created: ' . count($customerDefinitions));

        // ── 10. Expense Categories ─────────────────────────────────────

        $expenseCategoryNames = ['Rent', 'Electricity', 'Staff Salary'];

        foreach ($expenseCategoryNames as $name) {
            ExpenseCategory::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $name,
                ],
                ['is_active' => true],
            );
        }

        $this->command->info('Demo expense categories created: ' . count($expenseCategoryNames));
        $this->command->info('Demo tenant seeding complete.');
    }

    /**
     * Sample item definitions for the demo tenant.
     *
     * @return list<array<string, mixed>>
     */
    private function itemDefinitions(array $categories, array $manufacturers, Tenant $tenant): array
    {
        return [
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Paracetamol 500mg Tab',
                'composition'     => 'Paracetamol 500mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers['Cipla']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 20.00,
                'unit'            => 'Strip',
                'schedule'        => null,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Amoxicillin 250mg Cap',
                'composition'     => 'Amoxicillin 250mg',
                'category_id'     => $categories['Capsules']->id,
                'manufacturer_id' => $manufacturers['Cipla']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 25.00,
                'unit'            => 'Strip',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Omeprazole 20mg Cap',
                'composition'     => 'Omeprazole 20mg',
                'category_id'     => $categories['Capsules']->id,
                'manufacturer_id' => $manufacturers['Sun Pharma']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 22.00,
                'unit'            => 'Strip',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Azithromycin 500mg Tab',
                'composition'     => 'Azithromycin 500mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers['Cipla']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 28.00,
                'unit'            => 'Strip',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Cetirizine 10mg Tab',
                'composition'     => 'Cetirizine Hydrochloride 10mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers["Dr. Reddy's"]->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 18.00,
                'unit'            => 'Strip',
                'schedule'        => null,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Metformin 500mg Tab',
                'composition'     => 'Metformin Hydrochloride 500mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers['Sun Pharma']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 20.00,
                'unit'            => 'Strip',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Cough Syrup 100ml',
                'composition'     => 'Dextromethorphan 10mg + Phenylephrine 5mg',
                'category_id'     => $categories['Syrups']->id,
                'manufacturer_id' => $manufacturers['Cipla']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 22.00,
                'unit'            => 'Bottle',
                'schedule'        => null,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Dolo 650mg Tab',
                'composition'     => 'Paracetamol 650mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers['Cipla']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 20.00,
                'unit'            => 'Strip',
                'schedule'        => null,
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Pantoprazole 40mg Tab',
                'composition'     => 'Pantoprazole Sodium 40mg',
                'category_id'     => $categories['Tablets']->id,
                'manufacturer_id' => $manufacturers['Sun Pharma']->id,
                'hsn_code'        => '3004',
                'gst_percent'     => 12.00,
                'default_margin'  => 24.00,
                'unit'            => 'Strip',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
            [
                'tenant_id'       => $tenant->id,
                'name'            => 'Insulin Pen',
                'composition'     => 'Insulin Glargine 100 IU/ml',
                'category_id'     => $categories['Medical Devices']->id,
                'manufacturer_id' => $manufacturers['Sun Pharma']->id,
                'hsn_code'        => '9018',
                'gst_percent'     => 5.00,
                'default_margin'  => 15.00,
                'unit'            => 'Piece',
                'schedule'        => 'H',
                'is_active'       => true,
            ],
        ];
    }

    /**
     * Generate two batch definitions for the given item.
     *
     * Batch 1: Older batch expiring sooner (lower stock).
     * Batch 2: Newer batch expiring later (higher stock).
     *
     * @return list<array<string, mixed>>
     */
    private function batchDefinitions(Tenant $tenant, Item $item): array
    {
        // Use item ID to create deterministic but varied pricing.
        $basePurchasePrice = 20.00 + ($item->id * 7.50);
        $margin            = (float) $item->default_margin;
        $baseSellingPrice  = round($basePurchasePrice * (1 + $margin / 100), 2);
        $baseMrp           = round($baseSellingPrice * 1.10, 2); // MRP is ~10% above selling price.

        return [
            // Batch 1: Older batch, expiring sooner.
            [
                'tenant_id'      => $tenant->id,
                'item_id'        => $item->id,
                'batch_number'   => 'B' . str_pad((string) $item->id, 3, '0', STR_PAD_LEFT) . 'A',
                'expiry_date'    => Carbon::now()->addMonths(3)->toDateString(),
                'mrp'            => $baseMrp,
                'purchase_price' => $basePurchasePrice,
                'selling_price'  => $baseSellingPrice,
                'stock_quantity' => 50,
                'is_active'      => true,
            ],
            // Batch 2: Newer batch, expiring later.
            [
                'tenant_id'      => $tenant->id,
                'item_id'        => $item->id,
                'batch_number'   => 'B' . str_pad((string) $item->id, 3, '0', STR_PAD_LEFT) . 'B',
                'expiry_date'    => Carbon::now()->addMonths(12)->toDateString(),
                'mrp'            => round($baseMrp * 1.05, 2), // Slightly higher MRP for newer batch.
                'purchase_price' => round($basePurchasePrice * 1.03, 2),
                'selling_price'  => round($baseSellingPrice * 1.03, 2),
                'stock_quantity' => 100,
                'is_active'      => true,
            ],
        ];
    }
}
