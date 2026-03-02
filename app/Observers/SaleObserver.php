<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class SaleObserver
{
    /**
     * Handle the Sale "created" event.
     *
     * Logs an audit entry when a new sale is created, capturing the
     * invoice number, total amount, payment mode, and item count.
     */
    public function created(Sale $sale): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $sale->tenant_id,
                'user_id'        => Auth::id(),
                'action'         => 'created',
                'auditable_type' => Sale::class,
                'auditable_id'   => $sale->id,
                'old_values'     => null,
                'new_values'     => [
                    'invoice_number' => $sale->invoice_number,
                    'invoice_date'   => $sale->invoice_date?->toDateString(),
                    'customer_id'    => $sale->customer_id,
                    'total_amount'   => $sale->total_amount,
                    'payment_mode'   => $sale->payment_mode,
                    'paid_amount'    => $sale->paid_amount,
                    'balance_amount' => $sale->balance_amount,
                    'status'         => $sale->status,
                    'item_count'     => $sale->saleItems()->count(),
                ],
                'ip_address'     => Request::ip(),
                'user_agent'     => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the main flow
            Log::error('Failed to create audit log for sale', [
                'sale_id' => $sale->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
