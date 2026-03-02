<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'is_active'     => $this->is_active,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'role'          => $this->whenLoaded('role', fn () => [
                'id'          => $this->role->id,
                'name'        => $this->role->name,
                'permissions' => $this->when(
                    $this->role->relationLoaded('permissions'),
                    fn () => $this->role->permissions->pluck('name')
                ),
            ]),
            'tenant'        => $this->whenLoaded('tenant', fn () => [
                'id'   => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ]),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
