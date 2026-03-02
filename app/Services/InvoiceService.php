<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Sequence;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class InvoiceService
{
    /**
     * Generate an invoice number using the Sequence model.
     *
     * Uses atomic row locking to prevent duplicate numbers under concurrency.
     *
     * @param  int     $tenantId
     * @param  string  $type    Sequence type: 'sale', 'sale_return', 'purchase_return'
     * @param  string  $prefix  Number prefix: 'INV-', 'SR-', 'PR-'
     * @return string  Formatted invoice number (e.g., 'INV-000001')
     */
    public function generateInvoiceNumber(int $tenantId, string $type = 'sale', string $prefix = 'INV-'): string
    {
        return Sequence::nextNumber($tenantId, $type, $prefix);
    }

    /**
     * Generate a printable invoice PDF for a sale.
     *
     * Uses DomPDF to render the invoice Blade template into a downloadable PDF.
     *
     * @param  Sale  $sale  The sale to generate an invoice for (should be loaded with relationships)
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateInvoicePdf(Sale $sale): \Barryvdh\DomPDF\PDF
    {
        $data = $this->getInvoiceData($sale);

        $pdf = Pdf::loadView('pdf.invoice', $data);

        // A5 paper size is common for pharmacy invoices in India
        $pdf->setPaper('a5', 'portrait');
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');

        return $pdf;
    }

    /**
     * Get structured invoice data for display or PDF generation.
     *
     * @param  Sale  $sale  The sale to extract invoice data from
     * @return array
     */
    public function getInvoiceData(Sale $sale): array
    {
        // Eager load all relationships needed for the invoice
        $sale->loadMissing([
            'saleItems.item',
            'saleItems.batch',
            'customer',
            'createdBy',
        ]);

        $tenant = Tenant::find($sale->tenant_id);
        $settings = $tenant->settings ?? [];

        // Build line items
        $lineItems = [];
        foreach ($sale->saleItems as $index => $saleItem) {
            $lineItems[] = [
                'sr_no'            => $index + 1,
                'item_name'        => $saleItem->item->name ?? 'Unknown Item',
                'composition'      => $saleItem->item->composition ?? null,
                'hsn_code'         => $saleItem->item->hsn_code ?? null,
                'batch_number'     => $saleItem->batch->batch_number ?? '-',
                'expiry_date'      => $saleItem->batch->expiry_date
                    ? $saleItem->batch->expiry_date->format('m/Y')
                    : '-',
                'quantity'         => $saleItem->quantity,
                'unit'             => $saleItem->item->unit ?? 'strip',
                'mrp'              => (float) $saleItem->mrp,
                'selling_price'    => (float) $saleItem->selling_price,
                'discount_percent' => (float) $saleItem->discount_percent,
                'discount_amount'  => (float) $saleItem->discount_amount,
                'gst_percent'      => (float) $saleItem->gst_percent,
                'gst_amount'       => (float) $saleItem->gst_amount,
                'total_amount'     => (float) $saleItem->total_amount,
                'schedule'         => $saleItem->item->schedule ?? null,
            ];
        }

        // GST summary grouped by rate
        $gstSummary = [];
        foreach ($sale->saleItems as $saleItem) {
            $rate = (string) $saleItem->gst_percent;
            if (! isset($gstSummary[$rate])) {
                $gstSummary[$rate] = [
                    'rate'           => (float) $saleItem->gst_percent,
                    'taxable_amount' => 0,
                    'cgst'           => 0,
                    'sgst'           => 0,
                    'total_gst'      => 0,
                ];
            }

            $lineGross = $saleItem->quantity * $saleItem->selling_price;
            $taxable = $lineGross - (float) $saleItem->discount_amount;

            $gstSummary[$rate]['taxable_amount'] += $taxable;
            $gstSummary[$rate]['total_gst'] += (float) $saleItem->gst_amount;
            // Split GST equally between CGST and SGST (intra-state)
            $gstSummary[$rate]['cgst'] += (float) $saleItem->gst_amount / 2;
            $gstSummary[$rate]['sgst'] += (float) $saleItem->gst_amount / 2;
        }

        return [
            'sale'    => $sale,
            'tenant'  => [
                'name'            => $tenant->name,
                'owner_name'      => $tenant->owner_name,
                'drug_license_no' => $tenant->drug_license_no,
                'gstin'           => $tenant->gstin,
                'address_line1'   => $tenant->address_line1,
                'address_line2'   => $tenant->address_line2,
                'city'            => $tenant->city,
                'state'           => $tenant->state,
                'pincode'         => $tenant->pincode,
                'phone'           => $tenant->phone,
                'email'           => $tenant->email,
                'print_header'    => $settings['print_header'] ?? null,
                'print_footer'    => $settings['print_footer'] ?? null,
            ],
            'invoice' => [
                'number'             => $sale->invoice_number,
                'date'               => $sale->invoice_date->format($settings['date_format'] ?? 'd/m/Y'),
                'customer_name'      => $sale->customer?->name ?? 'Walk-in Customer',
                'customer_phone'     => $sale->customer?->phone ?? null,
                'customer_address'   => $sale->customer?->address ?? null,
                'doctor_name'        => $sale->doctor_name,
                'patient_name'       => $sale->patient_name,
                'created_by'         => $sale->createdBy?->name ?? 'System',
            ],
            'items'       => $lineItems,
            'gst_summary' => array_values($gstSummary),
            'totals'  => [
                'subtotal'            => (float) $sale->subtotal,
                'gst_amount'          => (float) $sale->gst_amount,
                'item_discount_total' => (float) $sale->item_discount_total,
                'invoice_discount'    => (float) $sale->invoice_discount,
                'roundoff'            => (float) $sale->roundoff,
                'total_amount'        => (float) $sale->total_amount,
                'paid_amount'         => (float) $sale->paid_amount,
                'balance_amount'      => (float) $sale->balance_amount,
                'payment_mode'        => $sale->payment_mode,
            ],
            'item_count'     => $sale->saleItems->count(),
            'total_quantity' => $sale->saleItems->sum('quantity'),
        ];
    }
}
