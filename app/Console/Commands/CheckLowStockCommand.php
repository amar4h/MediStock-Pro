<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medistock:check-low-stock
                            {--threshold= : Override the default low-stock threshold}
                            {--tenant= : Check a specific tenant ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active tenants for items with stock below the low-stock threshold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = $this->option('threshold')
            ? (int) $this->option('threshold')
            : config('medistock.inventory.low_stock_threshold', 10);

        $tenantId = $this->option('tenant');

        $this->info("Checking for items with total stock below {$threshold}...");

        $tenantsQuery = Tenant::query()
            ->where('subscription_status', '!=', 'cancelled');

        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }

        $tenants    = $tenantsQuery->get();
        $totalFound = 0;

        foreach ($tenants as $tenant) {
            // Find active items whose total batch stock is below the threshold.
            // This sums stock_quantity across all active batches for each item.
            $lowStockCount = Item::withoutGlobalScopes()
                ->where('items.tenant_id', $tenant->id)
                ->where('items.is_active', true)
                ->whereHas('batches') // Only items that have batches at all.
                ->whereRaw(
                    '(SELECT COALESCE(SUM(b.stock_quantity), 0) FROM batches b WHERE b.item_id = items.id AND b.is_active = 1) < ?',
                    [$threshold]
                )
                ->count();

            if ($lowStockCount > 0) {
                $message = "[Low Stock Alert] Tenant '{$tenant->name}' (ID: {$tenant->id}): {$lowStockCount} item(s) below threshold ({$threshold}).";

                Log::channel('daily')->warning($message);
                $this->warn($message);

                $totalFound += $lowStockCount;

                // Future: Dispatch notification to tenant owner / store manager.
                // Notification::send(..., new LowStockAlertNotification($lowStockCount, $threshold));
            }
        }

        if ($totalFound === 0) {
            $this->info('No low-stock items found across all tenants.');
        } else {
            $this->info("Total low-stock items found: {$totalFound}");
        }

        Log::channel('daily')->info("medistock:check-low-stock completed. Tenants checked: {$tenants->count()}, low-stock items: {$totalFound}.");

        return self::SUCCESS;
    }
}
