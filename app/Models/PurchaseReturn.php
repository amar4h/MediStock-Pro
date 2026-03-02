<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'return_number',
        'return_date',
        'total_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'total_amount' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
