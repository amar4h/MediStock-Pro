<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InvoiceScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'confidence'      => $this->ocr_confidence ? (float) $this->ocr_confidence : null,
            'extracted_data'  => $this->extracted_data,
            'warnings'        => $this->warnings,
            'error_message'   => $this->error_message,
            'image_url'       => $this->image_path ? Storage::url($this->image_path) : null,
            'processing_ms'   => $this->processing_ms,
            'purchase'        => $this->whenLoaded('purchase', fn () => [
                'id'             => $this->purchase->id,
                'invoice_number' => $this->purchase->invoice_number,
            ]),
            'user'            => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
