<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'item'             => $this->whenLoaded('item', fn () => [
                'id'   => $this->item->id,
                'name' => $this->item->name,
                'unit' => $this->item->unit ?? null,
            ]),
            'batch'            => $this->whenLoaded('batch', fn () => [
                'id'           => $this->batch->id,
                'batch_number' => $this->batch->batch_number,
                'expiry_date'  => $this->batch->expiry_date?->format('Y-m-d'),
            ]),
            'quantity'         => (int) $this->quantity,
            'mrp'              => (float) $this->mrp,
            'selling_price'    => (float) $this->selling_price,
            'purchase_price'   => (float) $this->purchase_price,
            'discount_percent' => (float) $this->discount_percent,
            'discount_amount'  => (float) $this->discount_amount,
            'gst_percent'      => (float) $this->gst_percent,
            'gst_amount'       => (float) $this->gst_amount,
            'total_amount'     => (float) $this->total_amount,
        ];
    }
}
