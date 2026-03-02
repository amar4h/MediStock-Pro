<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

class LedgerService
{
    /**
     * Get the current outstanding balance for a customer.
     *
     * Balance = opening_balance + sum(credit sale balances) - sum(payments received)
     * A positive balance means the customer owes money.
     *
     * @param  Customer  $customer
     * @return float
     */
    public function getCustomerBalance(Customer $customer): float
    {
        $openingBalance = (float) $customer->opening_balance;

        // Total balance from credit/partial sales (what customer owes from invoices)
        $totalSaleBalance = Sale::withoutGlobalScopes()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->sum('balance_amount');

        // Total payments received from customer
        $totalPayments = CustomerPayment::withoutGlobalScopes()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->sum('amount');

        return round($openingBalance + (float) $totalSaleBalance - (float) $totalPayments, 2);
    }

    /**
     * Get the chronological ledger for a customer (sales + payments).
     *
     * Returns a merged, time-ordered list of all credit transactions and payments.
     *
     * @param  Customer    $customer
     * @param  string|null $from  Start date (Y-m-d)
     * @param  string|null $to    End date (Y-m-d)
     * @return Collection
     */
    public function getCustomerLedger(Customer $customer, ?string $from = null, ?string $to = null): Collection
    {
        // Fetch credit sales
        $salesQuery = Sale::withoutGlobalScopes()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'invoice_number as reference',
                'invoice_date as date',
                'total_amount as debit',
            )
            ->selectRaw("0 as credit")
            ->selectRaw("'sale' as type")
            ->selectRaw("CONCAT('Invoice: ', invoice_number) as description");

        if ($from) {
            $salesQuery->where('invoice_date', '>=', $from);
        }
        if ($to) {
            $salesQuery->where('invoice_date', '<=', $to);
        }

        // Fetch payments received
        $paymentsQuery = CustomerPayment::withoutGlobalScopes()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->select(
                'id',
                'reference_no as reference',
                'payment_date as date',
            )
            ->selectRaw("0 as debit")
            ->selectRaw("amount as credit")
            ->selectRaw("'payment' as type")
            ->selectRaw("CONCAT('Payment via ', payment_mode) as description");

        if ($from) {
            $paymentsQuery->where('payment_date', '>=', $from);
        }
        if ($to) {
            $paymentsQuery->where('payment_date', '<=', $to);
        }

        // Merge and sort chronologically
        $ledgerEntries = $salesQuery->get()
            ->merge($paymentsQuery->get())
            ->sortBy('date')
            ->values();

        // Calculate running balance
        $runningBalance = (float) $customer->opening_balance;
        $entries = [];

        foreach ($ledgerEntries as $entry) {
            $runningBalance += (float) $entry->debit - (float) $entry->credit;

            $entries[] = [
                'id'              => $entry->id,
                'date'            => $entry->date,
                'type'            => $entry->type,
                'reference'       => $entry->reference,
                'description'     => $entry->description,
                'debit'           => round((float) $entry->debit, 2),
                'credit'          => round((float) $entry->credit, 2),
                'running_balance' => round($runningBalance, 2),
            ];
        }

        return collect($entries);
    }

    /**
     * Get the current outstanding balance for a supplier.
     *
     * Balance = opening_balance + sum(credit purchase balances) - sum(payments made)
     * A positive balance means we owe money to the supplier.
     *
     * @param  Supplier  $supplier
     * @return float
     */
    public function getSupplierBalance(Supplier $supplier): float
    {
        $openingBalance = (float) $supplier->opening_balance;

        // Total balance from credit/partial purchases (what we owe)
        $totalPurchaseBalance = Purchase::withoutGlobalScopes()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('supplier_id', $supplier->id)
            ->whereNull('deleted_at')
            ->sum('balance_amount');

        // Total payments made to supplier
        $totalPayments = SupplierPayment::withoutGlobalScopes()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('supplier_id', $supplier->id)
            ->sum('amount');

        return round($openingBalance + (float) $totalPurchaseBalance - (float) $totalPayments, 2);
    }

    /**
     * Get the chronological ledger for a supplier (purchases + payments).
     *
     * @param  Supplier    $supplier
     * @param  string|null $from  Start date (Y-m-d)
     * @param  string|null $to    End date (Y-m-d)
     * @return Collection
     */
    public function getSupplierLedger(Supplier $supplier, ?string $from = null, ?string $to = null): Collection
    {
        // Fetch purchases
        $purchasesQuery = Purchase::withoutGlobalScopes()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('supplier_id', $supplier->id)
            ->whereNull('deleted_at')
            ->select(
                'id',
                'invoice_number as reference',
                'invoice_date as date',
                'total_amount as debit',
            )
            ->selectRaw("0 as credit")
            ->selectRaw("'purchase' as type")
            ->selectRaw("CONCAT('Purchase: ', invoice_number) as description");

        if ($from) {
            $purchasesQuery->where('invoice_date', '>=', $from);
        }
        if ($to) {
            $purchasesQuery->where('invoice_date', '<=', $to);
        }

        // Fetch payments made
        $paymentsQuery = SupplierPayment::withoutGlobalScopes()
            ->where('tenant_id', $supplier->tenant_id)
            ->where('supplier_id', $supplier->id)
            ->select(
                'id',
                'reference_no as reference',
                'payment_date as date',
            )
            ->selectRaw("0 as debit")
            ->selectRaw("amount as credit")
            ->selectRaw("'payment' as type")
            ->selectRaw("CONCAT('Payment via ', payment_mode) as description");

        if ($from) {
            $paymentsQuery->where('payment_date', '>=', $from);
        }
        if ($to) {
            $paymentsQuery->where('payment_date', '<=', $to);
        }

        // Merge and sort chronologically
        $ledgerEntries = $purchasesQuery->get()
            ->merge($paymentsQuery->get())
            ->sortBy('date')
            ->values();

        // Calculate running balance
        $runningBalance = (float) $supplier->opening_balance;
        $entries = [];

        foreach ($ledgerEntries as $entry) {
            $runningBalance += (float) $entry->debit - (float) $entry->credit;

            $entries[] = [
                'id'              => $entry->id,
                'date'            => $entry->date,
                'type'            => $entry->type,
                'reference'       => $entry->reference,
                'description'     => $entry->description,
                'debit'           => round((float) $entry->debit, 2),
                'credit'          => round((float) $entry->credit, 2),
                'running_balance' => round($runningBalance, 2),
            ];
        }

        return collect($entries);
    }
}
