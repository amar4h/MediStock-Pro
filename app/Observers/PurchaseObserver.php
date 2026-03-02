<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class PurchaseObserver
{
    /**
     * Handle the Purchase "created" event.
     *
     * Logs an audit entry when a new purchase is recorded, capturing the
     * supplier invoice number, total amount, payment mode, and item count.
     */
    public function created(Purchase $purchase): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $purchase->tenant_id,
                'user_id'        => Auth::id(),
                'action'         => 'created',
                'auditable_type' => Purchase::class,
                'auditable_id'   => $purchase->id,
                'old_values'     => null,
                'new_values'     => [
                    'supplier_id'    => $purchase->supplier_id,
                    'invoice_number' => $purchase->invoice_number,
                    'invoice_date'   => $purchase->invoice_date?->toDateString(),
                    'total_amount'   => $purchase->total_amount,
                    'payment_mode'   => $purchase->payment_mode,
                    'paid_amount'    => $purchase->paid_amount,
                    'balance_amount' => $purchase->balance_amount,
                    'item_count'     => $purchase->purchaseItems()->count(),
                ],
                'ip_address'     => Request::ip(),
                'user_agent'     => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the main flow
            Log::error('Failed to create audit log for purchase', [
                'purchase_id' => $purchase->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
