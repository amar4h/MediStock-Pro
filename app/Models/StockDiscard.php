<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDiscard extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    /**
     * stock_discards table only has created_at, no updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'item_id',
        'batch_id',
        'quantity',
        'reason',
        'notes',
        'created_by',
        'discard_date',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'discard_date' => 'date',
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
