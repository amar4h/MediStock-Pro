<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
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
            'batch_number'     => $this->batch_number,
            'expiry_date'      => $this->expiry_date?->format('Y-m-d'),
            'quantity'         => (int) $this->quantity,
            'free_quantity'    => (int) $this->free_quantity,
            'mrp'              => (float) $this->mrp,
            'purchase_price'   => (float) $this->purchase_price,
            'selling_price'    => (float) $this->selling_price,
            'gst_percent'      => (float) $this->gst_percent,
            'gst_amount'       => (float) $this->gst_amount,
            'discount_percent' => (float) $this->discount_percent,
            'total_amount'     => (float) $this->total_amount,
        ];
    }
}
