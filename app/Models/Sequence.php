<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sequence extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * Sequences table only has updated_at, no created_at.
     */
    const CREATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'type',
        'prefix',
        'next_number',
    ];

    protected $casts = [
        'next_number' => 'integer',
    ];

    // ── Static Methods ───────────────────────────────────────────

    /**
     * Atomically increment and return the next formatted sequence number.
     *
     * Uses SELECT ... FOR UPDATE to prevent race conditions when multiple
     * users generate invoice numbers simultaneously.
     *
     * @param  int    $tenantId  The tenant ID
     * @param  string $type      Sequence type (e.g., 'sale', 'purchase_return', 'sale_return')
     * @param  string $prefix    Number prefix (e.g., 'INV-', 'SR-', 'PR-')
     * @return string            Formatted number (e.g., 'INV-000001')
     */
    public static function nextNumber(int $tenantId, string $type, string $prefix): string
    {
        return DB::transaction(function () use ($tenantId, $type, $prefix) {
            // Lock the row for update to prevent concurrent reads
            $sequence = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Create the sequence if it doesn't exist
                $sequence = static::withoutGlobalScopes()->create([
                    'tenant_id'   => $tenantId,
                    'type'        => $type,
                    'prefix'      => $prefix,
                    'next_number' => 2, // We'll use 1 as the first number
                ]);

                return $prefix . str_pad('1', 6, '0', STR_PAD_LEFT);
            }

            $currentNumber = $sequence->next_number;

            // Atomically increment
            $sequence->increment('next_number');

            return $prefix . str_pad((string) $currentNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
