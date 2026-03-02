<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'gstin',
        'drug_license_no',
        'address',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
