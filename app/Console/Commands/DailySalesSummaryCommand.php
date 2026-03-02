<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailySalesSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medistock:daily-sales-summary
                            {--date= : The date to generate summary for (YYYY-MM-DD, defaults to today)}
                            {--tenant= : Generate summary for a specific tenant ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a daily sales and profit summary for each active tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $tenantId = $this->option('tenant');

        $this->info("Generating daily sales summary for {$date->toDateString()}...");

        $tenantsQuery = Tenant::query()
            ->where('subscription_status', '!=', 'cancelled');

        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }

        $tenants   = $tenantsQuery->get();
        $summaries = [];

        foreach ($tenants as $tenant) {
            $summary = $this->generateTenantSummary($tenant, $date);
            $summaries[] = $summary;

            $this->displayTenantSummary($tenant, $summary);

            // Log the summary.
            Log::channel('daily')->info("Daily sales summary for '{$tenant->name}'", $summary);

            // Future: Email the summary to the tenant owner.
            // $owner = $tenant->users()->whereHas('role', fn($q) => $q->where('name', 'Owner'))->first();
            // if ($owner) {
            //     $owner->notify(new DailySalesSummaryNotification($summary));
            // }
        }

        $this->newLine();
        $this->info('Daily sales summary generation complete.');
        $this->info("Tenants processed: {$tenants->count()}");

        $totalSales  = collect($summaries)->sum('total_sales');
        $totalProfit = collect($summaries)->sum('total_profit');

        $this->info("Platform total sales: " . number_format($totalSales, 2));
        $this->info("Platform total profit: " . number_format($totalProfit, 2));

        Log::channel('daily')->info("medistock:daily-sales-summary completed for {$date->toDateString()}.", [
            'tenants_processed' => $tenants->count(),
            'platform_sales'    => $totalSales,
            'platform_profit'   => $totalProfit,
        ]);

        return self::SUCCESS;
    }

    /**
     * Generate the sales summary for a single tenant on the given date.
     *
     * @return array<string, mixed>
     */
    private function generateTenantSummary(Tenant $tenant, Carbon $date): array
    {
        // Total sales amount for the day.
        $salesData = Sale::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereDate('invoice_date', $date)
            ->where('status', '!=', 'voided')
            ->selectRaw('COUNT(*) as invoice_count, COALESCE(SUM(total_amount), 0) as total_sales, COALESCE(SUM(gst_amount), 0) as total_gst')
            ->first();

        // Calculate profit: sum of (selling_price - purchase_price) * quantity for all sale items.
        $profitData = SaleItem::withoutGlobalScopes()
            ->where('sale_items.tenant_id', $tenant->id)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.invoice_date', $date)
            ->where('sales.status', '!=', 'voided')
            ->selectRaw('COALESCE(SUM((sale_items.selling_price - sale_items.purchase_price) * sale_items.quantity), 0) as gross_profit')
            ->first();

        return [
            'tenant_id'     => $tenant->id,
            'tenant_name'   => $tenant->name,
            'date'          => $date->toDateString(),
            'invoice_count' => (int) ($salesData->invoice_count ?? 0),
            'total_sales'   => (float) ($salesData->total_sales ?? 0),
            'total_gst'     => (float) ($salesData->total_gst ?? 0),
            'total_profit'  => (float) ($profitData->gross_profit ?? 0),
        ];
    }

    /**
     * Display the tenant summary in the console.
     */
    private function displayTenantSummary(Tenant $tenant, array $summary): void
    {
        $this->newLine();
        $this->info("--- {$tenant->name} (ID: {$tenant->id}) ---");
        $this->line("  Date:           {$summary['date']}");
        $this->line("  Invoices:       {$summary['invoice_count']}");
        $this->line("  Total Sales:    Rs. " . number_format($summary['total_sales'], 2));
        $this->line("  GST Collected:  Rs. " . number_format($summary['total_gst'], 2));
        $this->line("  Gross Profit:   Rs. " . number_format($summary['total_profit'], 2));
    }
}
