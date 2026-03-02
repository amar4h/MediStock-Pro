<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     *
     * - Registers the TenantScope global scope so all queries are
     *   automatically filtered by the authenticated user's tenant_id.
     * - Listens to the "creating" event to auto-set tenant_id from the
     *   authenticated user when a new record is being created.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (Auth::check() && ! $model->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns this record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
