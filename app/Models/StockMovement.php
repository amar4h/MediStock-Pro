<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * stock_movements table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'item_id',
        'batch_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'stock_before',
        'stock_after',
        'created_by',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
