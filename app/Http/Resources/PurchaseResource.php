<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'invoice_number'  => $this->invoice_number,
            'invoice_date'    => $this->invoice_date?->format('Y-m-d'),
            'supplier'        => $this->whenLoaded('supplier', fn () => [
                'id'   => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'items'           => PurchaseItemResource::collection($this->whenLoaded('purchaseItems')),
            'items_count'     => $this->whenCounted('purchaseItems', $this->purchase_items_count),
            'subtotal'        => (float) $this->subtotal,
            'gst_amount'      => (float) $this->gst_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount'    => (float) $this->total_amount,
            'payment_mode'    => $this->payment_mode,
            'paid_amount'     => (float) $this->paid_amount,
            'balance_amount'  => (float) $this->balance_amount,
            'notes'           => $this->notes,
            'created_by'      => $this->whenLoaded('createdBy', fn () => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'returns'         => $this->whenLoaded('purchaseReturns'),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
