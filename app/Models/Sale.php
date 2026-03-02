<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, BelongsToTenant, Auditable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'gst_amount',
        'item_discount_total',
        'invoice_discount',
        'roundoff',
        'total_amount',
        'payment_mode',
        'paid_amount',
        'balance_amount',
        'status',
        'doctor_name',
        'patient_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'        => 'date',
        'subtotal'            => 'decimal:2',
        'gst_amount'          => 'decimal:2',
        'item_discount_total' => 'decimal:2',
        'invoice_discount'    => 'decimal:2',
        'roundoff'            => 'decimal:2',
        'total_amount'        => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'balance_amount'      => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleReturns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }
}
