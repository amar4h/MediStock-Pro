<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'composition'     => $this->composition,
            'category'        => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'manufacturer'    => $this->whenLoaded('manufacturer', fn () => [
                'id'   => $this->manufacturer->id,
                'name' => $this->manufacturer->name,
            ]),
            'hsn_code'        => $this->hsn_code,
            'gst_percent'     => (float) $this->gst_percent,
            'default_margin'  => (float) $this->default_margin,
            'barcode'         => $this->barcode,
            'unit'            => $this->unit,
            'schedule'        => $this->schedule,
            'is_active'       => $this->is_active,
            'batches_count'   => $this->whenCounted('batches', $this->batches_count),
            'total_stock'     => (int) ($this->total_stock ?? 0),
            'batches'         => BatchResource::collection($this->whenLoaded('batches')),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
