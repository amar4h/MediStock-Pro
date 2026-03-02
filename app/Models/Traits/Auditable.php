<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the Auditable trait.
     *
     * Listens to created, updated, and deleted Eloquent events and
     * creates an AuditLog entry capturing old_values, new_values,
     * user_id, ip_address, and user_agent.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->createAuditLog('created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }

            $oldValues = [];
            foreach (array_keys($dirty) as $key) {
                $oldValues[$key] = $model->getOriginal($key);
            }

            $model->createAuditLog('updated', $oldValues, $dirty);
        });

        static::deleted(function ($model) {
            $model->createAuditLog('deleted', $model->getOriginal(), []);
        });
    }

    /**
     * Create an audit log entry for this model.
     */
    protected function createAuditLog(string $action, array $oldValues, array $newValues): void
    {
        // Exclude sensitive fields from audit
        $excludeFields = ['password', 'remember_token'];
        $oldValues = array_diff_key($oldValues, array_flip($excludeFields));
        $newValues = array_diff_key($newValues, array_flip($excludeFields));

        // Remove timestamp fields from new values on create to reduce noise
        $timestampFields = ['created_at', 'updated_at', 'deleted_at'];
        if ($action === 'created') {
            $newValues = array_diff_key($newValues, array_flip($timestampFields));
        }

        AuditLog::create([
            'tenant_id'      => $this->tenant_id ?? (Auth::check() ? Auth::user()->tenant_id : null),
            'user_id'        => Auth::id(),
            'action'         => $action,
            'auditable_type' => get_class($this),
            'auditable_id'   => $this->getKey(),
            'old_values'     => ! empty($oldValues) ? $oldValues : null,
            'new_values'     => ! empty($newValues) ? $newValues : null,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }

    /**
     * Get all audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
