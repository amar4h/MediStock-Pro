<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\StockDiscard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class StockDiscardObserver
{
    /**
     * Handle the StockDiscard "created" event.
     *
     * Logs an audit entry when stock is discarded, capturing the
     * item, batch, quantity, reason, and discard date.
     */
    public function created(StockDiscard $discard): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $discard->tenant_id,
                'user_id'        => Auth::id(),
                'action'         => 'created',
                'auditable_type' => StockDiscard::class,
                'auditable_id'   => $discard->id,
                'old_values'     => null,
                'new_values'     => [
                    'item_id'      => $discard->item_id,
                    'batch_id'     => $discard->batch_id,
                    'quantity'     => $discard->quantity,
                    'reason'       => $discard->reason,
                    'notes'        => $discard->notes,
                    'discard_date' => $discard->discard_date?->toDateString(),
                ],
                'ip_address'     => Request::ip(),
                'user_agent'     => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Audit logging should never break the main flow
            Log::error('Failed to create audit log for stock discard', [
                'discard_id' => $discard->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
