<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isExpired    = $this->expiry_date->isPast();
        $daysToExpiry = $isExpired ? 0 : Carbon::today()->diffInDays($this->expiry_date);

        return [
            'id'              => $this->id,
            'item_id'         => $this->item_id,
            'item'            => $this->whenLoaded('item', fn () => [
                'id'   => $this->item->id,
                'name' => $this->item->name,
                'unit' => $this->item->unit,
            ]),
            'batch_number'    => $this->batch_number,
            'expiry_date'     => $this->expiry_date->format('Y-m-d'),
            'mrp'             => (float) $this->mrp,
            'purchase_price'  => (float) $this->purchase_price,
            'selling_price'   => (float) $this->selling_price,
            'stock_quantity'  => (int) $this->stock_quantity,
            'is_expired'      => $isExpired,
            'days_to_expiry'  => (int) $daysToExpiry,
            'is_active'       => $this->is_active,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
