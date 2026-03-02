<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * purchase_return_items table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'purchase_return_id',
        'tenant_id',
        'item_id',
        'batch_id',
        'quantity',
        'purchase_price',
        'total_amount',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'purchase_price' => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
