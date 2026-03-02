<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sale_id',
        'return_number',
        'return_date',
        'total_amount',
        'return_type',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date'  => 'date',
        'total_amount' => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
