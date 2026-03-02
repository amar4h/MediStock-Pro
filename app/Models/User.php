<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'      => 'hashed',
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Helpers ──────────────────────────────────────────────────

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role
            && $this->role->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }
}
