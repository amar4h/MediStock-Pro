<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'invoice_number'      => $this->invoice_number,
            'invoice_date'        => $this->invoice_date?->format('Y-m-d'),
            'customer'            => $this->whenLoaded('customer', fn () => [
                'id'    => $this->customer->id,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'items'               => SaleItemResource::collection($this->whenLoaded('saleItems')),
            'items_count'         => $this->whenCounted('saleItems', $this->sale_items_count),
            'subtotal'            => (float) $this->subtotal,
            'gst_amount'          => (float) $this->gst_amount,
            'item_discount_total' => (float) $this->item_discount_total,
            'invoice_discount'    => (float) $this->invoice_discount,
            'roundoff'            => (float) $this->roundoff,
            'total_amount'        => (float) $this->total_amount,
            'payment_mode'        => $this->payment_mode,
            'paid_amount'         => (float) $this->paid_amount,
            'balance_amount'      => (float) $this->balance_amount,
            'status'              => $this->status,
            'doctor_name'         => $this->doctor_name,
            'patient_name'        => $this->patient_name,
            'notes'               => $this->notes,
            'created_by'          => $this->whenLoaded('createdBy', fn () => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'returns'             => $this->whenLoaded('saleReturns'),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
