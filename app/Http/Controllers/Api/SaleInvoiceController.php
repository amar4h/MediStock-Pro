<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SaleInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * Get invoice data for printing / frontend rendering.
     */
    public function show(Sale $sale): JsonResponse
    {
        $sale->load([
            'customer',
            'saleItems.item:id,name,unit,composition,hsn_code,schedule',
            'saleItems.batch:id,batch_number,expiry_date,mrp',
            'createdBy:id,name',
        ]);

        $tenant = auth()->user()->tenant;

        return response()->json([
            'success' => true,
            'data'    => [
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
                ],
                'sale'    => [
                    'id'               => $sale->id,
                    'invoice_number'   => $sale->invoice_number,
                    'invoice_date'     => $sale->invoice_date->format('d/m/Y'),
                    'customer'         => $sale->customer ? [
                        'name'    => $sale->customer->name,
                        'phone'   => $sale->customer->phone,
                        'address' => $sale->customer->address,
                    ] : null,
                    'doctor_name'      => $sale->doctor_name,
                    'patient_name'     => $sale->patient_name,
                    'items'            => $sale->saleItems->map(fn ($si) => [
                        'name'             => $si->item->name,
                        'composition'      => $si->item->composition,
                        'hsn_code'         => $si->item->hsn_code,
                        'schedule'         => $si->item->schedule,
                        'unit'             => $si->item->unit,
                        'batch_number'     => $si->batch->batch_number,
                        'expiry_date'      => $si->batch->expiry_date->format('m/Y'),
                        'quantity'         => $si->quantity,
                        'mrp'              => $si->mrp,
                        'selling_price'    => $si->selling_price,
                        'discount_percent' => $si->discount_percent,
                        'discount_amount'  => $si->discount_amount,
                        'gst_percent'      => $si->gst_percent,
                        'gst_amount'       => $si->gst_amount,
                        'total_amount'     => $si->total_amount,
                    ]),
                    'subtotal'            => $sale->subtotal,
                    'gst_amount'          => $sale->gst_amount,
                    'item_discount_total' => $sale->item_discount_total,
                    'invoice_discount'    => $sale->invoice_discount,
                    'roundoff'            => $sale->roundoff,
                    'total_amount'        => $sale->total_amount,
                    'payment_mode'        => $sale->payment_mode,
                    'paid_amount'         => $sale->paid_amount,
                    'balance_amount'      => $sale->balance_amount,
                    'created_by'          => $sale->createdBy->name,
                ],
            ],
        ]);
    }

    /**
     * Download invoice as PDF.
     */
    public function download(Sale $sale): Response
    {
        $sale->load([
            'customer',
            'saleItems.item:id,name,unit,composition,hsn_code,schedule',
            'saleItems.batch:id,batch_number,expiry_date,mrp',
            'createdBy:id,name',
        ]);

        $pdf = $this->invoiceService->generatePdf($sale);

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"invoice-{$sale->invoice_number}.pdf\"",
        ]);
    }
}
