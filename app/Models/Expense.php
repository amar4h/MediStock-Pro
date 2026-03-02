<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'expense_category_id',
        'amount',
        'expense_date',
        'description',
        'payment_mode',
        'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
