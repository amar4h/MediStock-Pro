<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medistock:check-expiry
                            {--days=30 : Number of days to check for near-expiry batches}
                            {--tenant= : Check a specific tenant ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active tenants for batches expiring within the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days     = (int) $this->option('days');
        $tenantId = $this->option('tenant');
        $cutoff   = Carbon::today()->addDays($days);

        $this->info("Checking for batches expiring within {$days} days (by {$cutoff->toDateString()})...");

        $tenantsQuery = Tenant::query()
            ->where('subscription_status', '!=', 'cancelled');

        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }

        $tenants    = $tenantsQuery->get();
        $totalFound = 0;

        foreach ($tenants as $tenant) {
            $count = Batch::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('stock_quantity', '>', 0)
                ->where('expiry_date', '>', Carbon::today())
                ->where('expiry_date', '<=', $cutoff)
                ->count();

            if ($count > 0) {
                $message = "[Expiry Alert] Tenant '{$tenant->name}' (ID: {$tenant->id}): {$count} batch(es) expiring within {$days} days.";

                Log::channel('daily')->warning($message);
                $this->warn($message);

                $totalFound += $count;

                // Future: Dispatch notification to tenant owner.
                // Notification::send($tenant->users()->whereHas('role', fn($q) => $q->where('name', 'Owner'))->get(), new ExpiryAlertNotification($count, $days));
            }
        }

        if ($totalFound === 0) {
            $this->info('No near-expiry batches found across all tenants.');
        } else {
            $this->info("Total near-expiry batches found: {$totalFound}");
        }

        Log::channel('daily')->info("medistock:check-expiry completed. Tenants checked: {$tenants->count()}, near-expiry batches: {$totalFound}.");

        return self::SUCCESS;
    }
}
