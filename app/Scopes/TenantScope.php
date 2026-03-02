<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Adds a WHERE tenant_id = ? clause using the authenticated user's tenant_id.
     * If no user is authenticated, adds an impossible condition to prevent data leaks.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            $builder->where($model->qualifyColumn('tenant_id'), Auth::user()->tenant_id);
        }
    }
}
