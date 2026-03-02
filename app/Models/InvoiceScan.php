<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceScan extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'purchase_id',
        'image_path',
        'status',
        'raw_ocr_text',
        'ocr_confidence',
        'extracted_data',
        'warnings',
        'error_message',
        'processing_ms',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'warnings'       => 'array',
        'ocr_confidence' => 'decimal:4',
        'processing_ms'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
