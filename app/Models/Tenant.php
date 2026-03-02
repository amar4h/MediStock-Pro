<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'owner_name',
        'email',
        'phone',
        'drug_license_no',
        'gstin',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'subscription_status',
        'subscription_plan',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
    ];

    protected $casts = [
        'settings'             => 'array',
        'subscription_status'  => 'string',
        'trial_ends_at'        => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function manufacturers(): HasMany
    {
        return $this->hasMany(Manufacturer::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function invoiceScans(): HasMany
    {
        return $this->hasMany(InvoiceScan::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockDiscards(): HasMany
    {
        return $this->hasMany(StockDiscard::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
