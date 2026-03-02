<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, BelongsToTenant, Auditable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'gst_amount',
        'discount_amount',
        'total_amount',
        'payment_mode',
        'paid_amount',
        'balance_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'subtotal'        => 'decimal:2',
        'gst_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'balance_amount'  => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
