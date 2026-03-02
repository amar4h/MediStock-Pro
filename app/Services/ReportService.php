<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Sales report grouped by period.
     *
     * @param  int          $tenantId
     * @param  string       $period   'daily', 'weekly', 'monthly', 'annual'
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return Collection
     */
    public function salesReport(int $tenantId, string $period, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = Sale::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        if ($from) {
            $query->where('invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->where('invoice_date', '<=', $to->toDateString());
        }

        $groupBy = match ($period) {
            'daily'   => "DATE_FORMAT(invoice_date, '%Y-%m-%d')",
            'weekly'  => "DATE_FORMAT(invoice_date, '%x-W%v')",   // ISO year-week
            'monthly' => "DATE_FORMAT(invoice_date, '%Y-%m')",
            'annual'  => "DATE_FORMAT(invoice_date, '%Y')",
            default   => "DATE_FORMAT(invoice_date, '%Y-%m-%d')",
        };

        return $query->select(
            DB::raw("{$groupBy} as period"),
            DB::raw('COUNT(*) as total_invoices'),
            DB::raw('SUM(total_amount) as total_sales'),
            DB::raw('SUM(gst_amount) as total_gst'),
            DB::raw('SUM(item_discount_total + invoice_discount) as total_discount'),
            DB::raw('SUM(paid_amount) as total_received'),
            DB::raw('SUM(balance_amount) as total_outstanding'),
        )
            ->groupBy(DB::raw($groupBy))
            ->orderBy(DB::raw($groupBy))
            ->get();
    }

    /**
     * Gross profit report: sum of (selling_price - purchase_price) * quantity per sale item.
     *
     * @param  int          $tenantId
     * @param  string       $period   'daily', 'weekly', 'monthly', 'annual'
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return Collection
     */
    public function profitReport(int $tenantId, string $period, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $groupBy = match ($period) {
            'daily'   => "DATE_FORMAT(sales.invoice_date, '%Y-%m-%d')",
            'weekly'  => "DATE_FORMAT(sales.invoice_date, '%x-W%v')",
            'monthly' => "DATE_FORMAT(sales.invoice_date, '%Y-%m')",
            'annual'  => "DATE_FORMAT(sales.invoice_date, '%Y')",
            default   => "DATE_FORMAT(sales.invoice_date, '%Y-%m-%d')",
        };

        $query = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');

        if ($from) {
            $query->where('sales.invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->where('sales.invoice_date', '<=', $to->toDateString());
        }

        return $query->select(
            DB::raw("{$groupBy} as period"),
            DB::raw('SUM(sale_items.total_amount) as total_revenue'),
            DB::raw('SUM(sale_items.purchase_price * sale_items.quantity) as total_cost'),
            DB::raw('SUM(sale_items.total_amount) - SUM(sale_items.purchase_price * sale_items.quantity) as gross_profit'),
            DB::raw('CASE WHEN SUM(sale_items.total_amount) > 0
                THEN ROUND(((SUM(sale_items.total_amount) - SUM(sale_items.purchase_price * sale_items.quantity)) / SUM(sale_items.total_amount)) * 100, 2)
                ELSE 0
            END as profit_margin_percent'),
        )
            ->groupBy(DB::raw($groupBy))
            ->orderBy(DB::raw($groupBy))
            ->get();
    }

    /**
     * Net profit report: Gross profit from sales minus total expenses.
     *
     * @param  int          $tenantId
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return array
     */
    public function netProfitReport(int $tenantId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        // Gross profit from sales
        $profitQuery = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');

        if ($from) {
            $profitQuery->where('sales.invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $profitQuery->where('sales.invoice_date', '<=', $to->toDateString());
        }

        $grossProfitResult = $profitQuery->select(
            DB::raw('SUM(sale_items.total_amount) as total_revenue'),
            DB::raw('SUM(sale_items.purchase_price * sale_items.quantity) as total_cost'),
        )->first();

        $totalRevenue = (float) ($grossProfitResult->total_revenue ?? 0);
        $totalCost = (float) ($grossProfitResult->total_cost ?? 0);
        $grossProfit = $totalRevenue - $totalCost;

        // Total expenses
        $expenseQuery = Expense::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);

        if ($from) {
            $expenseQuery->where('expense_date', '>=', $from->toDateString());
        }
        if ($to) {
            $expenseQuery->where('expense_date', '<=', $to->toDateString());
        }

        $totalExpenses = (float) $expenseQuery->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;

        return [
            'total_revenue'        => round($totalRevenue, 2),
            'total_cost'           => round($totalCost, 2),
            'gross_profit'         => round($grossProfit, 2),
            'total_expenses'       => round($totalExpenses, 2),
            'net_profit'           => round($netProfit, 2),
            'gross_margin_percent' => $totalRevenue > 0
                ? round(($grossProfit / $totalRevenue) * 100, 2)
                : 0,
            'net_margin_percent'   => $totalRevenue > 0
                ? round(($netProfit / $totalRevenue) * 100, 2)
                : 0,
        ];
    }

    /**
     * Expense report grouped by category.
     *
     * @param  int          $tenantId
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return Collection
     */
    public function expenseReport(int $tenantId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = Expense::withoutGlobalScopes()
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.tenant_id', $tenantId);

        if ($from) {
            $query->where('expenses.expense_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->where('expenses.expense_date', '<=', $to->toDateString());
        }

        return $query->select(
            'expense_categories.id as category_id',
            'expense_categories.name as category_name',
            DB::raw('COUNT(*) as total_entries'),
            DB::raw('SUM(expenses.amount) as total_amount'),
        )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc(DB::raw('SUM(expenses.amount)'))
            ->get();
    }

    /**
     * GST summary: total GST collected (on sales) and GST paid (on purchases).
     *
     * @param  int          $tenantId
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return array
     */
    public function gstSummary(int $tenantId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        // GST collected from sales
        $salesGstQuery = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');

        if ($from) {
            $salesGstQuery->where('sales.invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $salesGstQuery->where('sales.invoice_date', '<=', $to->toDateString());
        }

        $gstCollected = $salesGstQuery->select(
            'sale_items.gst_percent',
            DB::raw('SUM(sale_items.gst_amount) as total_gst'),
            DB::raw('SUM(sale_items.total_amount - sale_items.gst_amount) as taxable_amount'),
        )
            ->groupBy('sale_items.gst_percent')
            ->orderBy('sale_items.gst_percent')
            ->get();

        // GST paid on purchases
        $purchaseGstQuery = PurchaseItem::withoutGlobalScopes()
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.tenant_id', $tenantId)
            ->whereNull('purchases.deleted_at');

        if ($from) {
            $purchaseGstQuery->where('purchases.invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $purchaseGstQuery->where('purchases.invoice_date', '<=', $to->toDateString());
        }

        $gstPaid = $purchaseGstQuery->select(
            'purchase_items.gst_percent',
            DB::raw('SUM(purchase_items.gst_amount) as total_gst'),
            DB::raw('SUM(purchase_items.total_amount - purchase_items.gst_amount) as taxable_amount'),
        )
            ->groupBy('purchase_items.gst_percent')
            ->orderBy('purchase_items.gst_percent')
            ->get();

        $totalGstCollected = $gstCollected->sum('total_gst');
        $totalGstPaid = $gstPaid->sum('total_gst');

        return [
            'gst_collected'       => $gstCollected,
            'gst_paid'            => $gstPaid,
            'total_gst_collected' => round((float) $totalGstCollected, 2),
            'total_gst_paid'      => round((float) $totalGstPaid, 2),
            'net_gst_liability'   => round((float) $totalGstCollected - (float) $totalGstPaid, 2),
        ];
    }

    /**
     * Item-wise profit breakdown.
     *
     * @param  int          $tenantId
     * @param  Carbon|null  $from
     * @param  Carbon|null  $to
     * @return Collection
     */
    public function itemWiseProfit(int $tenantId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = SaleItem::withoutGlobalScopes()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('items', 'sale_items.item_id', '=', 'items.id')
            ->where('sale_items.tenant_id', $tenantId)
            ->whereNull('sales.deleted_at');

        if ($from) {
            $query->where('sales.invoice_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->where('sales.invoice_date', '<=', $to->toDateString());
        }

        return $query->select(
            'items.id as item_id',
            'items.name as item_name',
            'items.composition',
            DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
            DB::raw('SUM(sale_items.total_amount) as total_revenue'),
            DB::raw('SUM(sale_items.purchase_price * sale_items.quantity) as total_cost'),
            DB::raw('SUM(sale_items.total_amount) - SUM(sale_items.purchase_price * sale_items.quantity) as profit'),
            DB::raw('CASE WHEN SUM(sale_items.total_amount) > 0
                THEN ROUND(((SUM(sale_items.total_amount) - SUM(sale_items.purchase_price * sale_items.quantity)) / SUM(sale_items.total_amount)) * 100, 2)
                ELSE 0
            END as margin_percent'),
        )
            ->groupBy('items.id', 'items.name', 'items.composition')
            ->orderByDesc(DB::raw('SUM(sale_items.total_amount) - SUM(sale_items.purchase_price * sale_items.quantity)'))
            ->get();
    }
}
