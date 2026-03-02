<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * Get all dashboard KPIs for the owner/manager dashboard.
     *
     * @param  int  $tenantId
     * @return array
     */
    public function getDashboardData(int $tenantId): array
    {
        $today = Carbon::today()->toDateString();

        return [
            'todaySales'                => $this->getTodaySales($tenantId, $today),
            'todayProfit'               => $this->getTodayProfit($tenantId, $today),
            'outstandingCustomerCredit' => $this->getOutstandingCustomerCredit($tenantId),
            'pendingSupplierPayment'    => $this->getPendingSupplierPayment($tenantId),
            'lowStockCount'             => $this->getLowStockCount($tenantId),
            'nearExpiryCount'           => $this->getNearExpiryCount($tenantId, 30),
        ];
    }

    /**
     * Sum of today's sale totals.
     */
    private function getTodaySales(int $tenantId, string $today): float
    {
        $total = Sale::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('invoice_date', $today)
            ->whereNull('deleted_at')
            ->sum('total_amount');

        return round((float) $total, 2);
    }

    /**
     * Sum of today's gross profit: sum((selling_price - purchase_price) * quantity) for today's sales.
     */
    private function getTodayProfit(int $tenantId, string $today): float
    {
        $result = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.tenant_id', $tenantId)
            ->where('sales.invoice_date', $today)
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('SUM((sale_items.selling_price - sale_items.purchase_price) * sale_items.quantity) as gross_profit')
            )
            ->first();

        return round((float) ($result->gross_profit ?? 0), 2);
    }

    /**
     * Total outstanding customer credit balance across all customers.
     */
    private function getOutstandingCustomerCredit(int $tenantId): float
    {
        $customers = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->get();

        $total = 0;
        foreach ($customers as $customer) {
            $balance = $this->ledgerService->getCustomerBalance($customer);
            if ($balance > 0) {
                $total += $balance;
            }
        }

        return round($total, 2);
    }

    /**
     * Total pending payment to all suppliers.
     */
    private function getPendingSupplierPayment(int $tenantId): float
    {
        $suppliers = Supplier::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->get();

        $total = 0;
        foreach ($suppliers as $supplier) {
            $balance = $this->ledgerService->getSupplierBalance($supplier);
            if ($balance > 0) {
                $total += $balance;
            }
        }

        return round($total, 2);
    }

    /**
     * Count of items that are below the low-stock threshold.
     */
    private function getLowStockCount(int $tenantId): int
    {
        return $this->stockService->getLowStockItems($tenantId)->count();
    }

    /**
     * Count of batches expiring within the specified number of days.
     */
    private function getNearExpiryCount(int $tenantId, int $days): int
    {
        return Batch::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('stock_quantity', '>', 0)
            ->where('expiry_date', '>', Carbon::today())
            ->where('expiry_date', '<=', Carbon::today()->addDays($days))
            ->count();
    }
}
