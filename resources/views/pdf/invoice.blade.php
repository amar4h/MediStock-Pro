<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $sale->invoice_number ?? '' }}</title>

    <style>
        /* ============================================================
           DomPDF Invoice Template - Inline CSS
           ============================================================ */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        /* Page Setup */
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 15mm 10mm;
        }

        /* ---- Store Header ---- */
        .header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 8pt;
            margin-bottom: 10pt;
        }

        .header .store-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2pt;
        }

        .header .store-details {
            font-size: 8pt;
            color: #555;
            line-height: 1.5;
        }

        .header .store-details span {
            margin: 0 4pt;
        }

        .header .tax-invoice-label {
            display: inline-block;
            background-color: #1e40af;
            color: #fff;
            font-size: 9pt;
            font-weight: bold;
            padding: 2pt 12pt;
            margin-top: 6pt;
            letter-spacing: 1pt;
        }

        /* ---- Invoice Details ---- */
        .invoice-meta {
            width: 100%;
            margin-bottom: 10pt;
        }

        .invoice-meta td {
            vertical-align: top;
            font-size: 9pt;
        }

        .invoice-meta .label {
            font-weight: bold;
            color: #555;
            width: 80pt;
        }

        .invoice-meta .value {
            color: #222;
        }

        .meta-left {
            width: 50%;
        }

        .meta-right {
            width: 50%;
            text-align: right;
        }

        .meta-right .label {
            text-align: right;
        }

        .divider {
            border-top: 1px solid #ccc;
            margin: 6pt 0;
        }

        /* ---- Items Table ---- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
            font-size: 8.5pt;
        }

        .items-table thead {
            background-color: #f0f4ff;
        }

        .items-table th {
            border: 1px solid #bbb;
            padding: 4pt 3pt;
            text-align: center;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #333;
            background-color: #e8ecf7;
        }

        .items-table td {
            border: 1px solid #ddd;
            padding: 3pt 3pt;
            vertical-align: middle;
        }

        .items-table .text-left {
            text-align: left;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fafbfd;
        }

        .items-table .item-name {
            text-align: left;
            padding-left: 4pt;
            max-width: 120pt;
            word-wrap: break-word;
        }

        /* ---- Totals Section ---- */
        .totals-section {
            width: 100%;
            margin-bottom: 8pt;
        }

        .totals-section .left-col {
            width: 55%;
            vertical-align: top;
        }

        .totals-section .right-col {
            width: 45%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .totals-table td {
            padding: 2pt 4pt;
        }

        .totals-table .total-label {
            text-align: right;
            color: #555;
            padding-right: 8pt;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
            color: #222;
            width: 80pt;
        }

        .totals-table .grand-total td {
            border-top: 2px solid #1e40af;
            padding-top: 4pt;
            font-size: 11pt;
            color: #1e40af;
        }

        /* ---- Amount in Words ---- */
        .amount-words {
            font-size: 9pt;
            font-style: italic;
            color: #444;
            border-top: 1px solid #ddd;
            padding-top: 5pt;
            margin-top: 4pt;
            margin-bottom: 10pt;
        }

        .amount-words strong {
            font-style: normal;
            color: #222;
        }

        /* ---- Footer ---- */
        .footer {
            text-align: center;
            font-size: 8pt;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 8pt;
            margin-top: 15pt;
        }

        .footer .thank-you {
            font-size: 10pt;
            color: #555;
            font-weight: bold;
            margin-bottom: 3pt;
        }

        /* ---- Signature Area ---- */
        .signature-area {
            width: 100%;
            margin-top: 30pt;
        }

        .signature-area td {
            width: 50%;
            font-size: 8pt;
            color: #555;
            vertical-align: bottom;
        }

        .signature-area .sig-line {
            border-top: 1px solid #999;
            width: 120pt;
            margin-top: 30pt;
            padding-top: 3pt;
        }

        .signature-area .sig-right {
            text-align: right;
        }

        .signature-area .sig-right .sig-line {
            margin-left: auto;
        }

        /* ---- Utility ---- */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    {{-- ============================================================
         STORE HEADER
         ============================================================ --}}
    <div class="header">
        <div class="store-name">{{ $tenant->name ?? 'Medical Store' }}</div>
        <div class="store-details">
            @if(!empty($tenant->address))
            {{ $tenant->address }}<br>
            @endif
            @if(!empty($tenant->phone))
            <span>Phone: {{ $tenant->phone }}</span>
            @endif
            @if(!empty($tenant->gstin))
            <span>GSTIN: {{ $tenant->gstin }}</span>
            @endif
            @if(!empty($tenant->drug_license))
            <span>Drug Lic: {{ $tenant->drug_license }}</span>
            @endif
        </div>
        <div class="tax-invoice-label">TAX INVOICE</div>
    </div>

    {{-- ============================================================
         INVOICE DETAILS
         ============================================================ --}}
    <table class="invoice-meta">
        <tr>
            <td class="meta-left">
                <table>
                    <tr>
                        <td class="label">Invoice No:</td>
                        <td class="value"><strong>{{ $sale->invoice_number ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Date:</td>
                        <td class="value">{{ isset($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y h:i A') : '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Payment:</td>
                        <td class="value">{{ ucfirst($sale->payment_mode ?? 'Cash') }}</td>
                    </tr>
                </table>
            </td>
            <td class="meta-right">
                <table style="margin-left: auto;">
                    <tr>
                        <td class="label">Patient:</td>
                        <td class="value">{{ $sale->patient_name ?? $sale->customer_name ?? 'Walk-in' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Doctor:</td>
                        <td class="value">{{ $sale->doctor_name ?? '-' }}</td>
                    </tr>
                    @if(!empty($sale->customer_phone))
                    <tr>
                        <td class="label">Phone:</td>
                        <td class="value">{{ $sale->customer_phone }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ============================================================
         ITEMS TABLE
         ============================================================ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 20pt;">Sr</th>
                <th class="item-name">Item Name</th>
                <th style="width: 45pt;">Batch</th>
                <th style="width: 42pt;">Expiry</th>
                <th style="width: 38pt;">HSN</th>
                <th style="width: 25pt;">Qty</th>
                <th style="width: 42pt;">MRP</th>
                <th style="width: 42pt;">Rate</th>
                <th style="width: 30pt;">Disc%</th>
                <th style="width: 30pt;">GST%</th>
                <th style="width: 50pt;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $sr = 1; @endphp
            @foreach(($sale->items ?? []) as $item)
            <tr>
                <td class="text-center">{{ $sr++ }}</td>
                <td class="item-name">{{ $item->item->name ?? $item->item_name ?? '' }}</td>
                <td class="text-center">{{ $item->batch->batch_number ?? $item->batch_number ?? '' }}</td>
                <td class="text-center">
                    @if(!empty($item->batch->expiry_date ?? $item->expiry_date))
                    {{ \Carbon\Carbon::parse($item->batch->expiry_date ?? $item->expiry_date)->format('m/Y') }}
                    @endif
                </td>
                <td class="text-center" style="font-size: 7pt;">{{ $item->hsn_code ?? $item->item->hsn_code ?? '' }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right">{{ number_format($item->mrp ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($item->price ?? $item->selling_price ?? 0, 2) }}</td>
                <td class="text-center">{{ $item->discount > 0 ? number_format($item->discount, 1) : '-' }}</td>
                <td class="text-center">{{ number_format($item->gst_percent ?? 0, 0) }}%</td>
                <td class="text-right"><strong>{{ number_format($item->amount ?? ($item->qty * ($item->price ?? 0) * (1 - ($item->discount ?? 0)/100)), 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ============================================================
         TOTALS
         ============================================================ --}}
    <table class="totals-section">
        <tr>
            <td class="left-col">
                {{-- Amount in words --}}
                <div class="amount-words">
                    <strong>Amount in Words:</strong><br>
                    {{ $amountInWords ?? 'Rupees ' . number_format($sale->grand_total ?? 0, 2) . ' Only' }}
                </div>
            </td>
            <td class="right-col">
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Subtotal:</td>
                        <td class="total-value">{{ number_format($sale->subtotal ?? 0, 2) }}</td>
                    </tr>
                    @php
                        $totalGst = $sale->gst_amount ?? 0;
                        $cgst = $totalGst / 2;
                        $sgst = $totalGst / 2;
                    @endphp
                    <tr>
                        <td class="total-label">CGST:</td>
                        <td class="total-value">{{ number_format($cgst, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">SGST:</td>
                        <td class="total-value">{{ number_format($sgst, 2) }}</td>
                    </tr>
                    @if(($sale->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="total-label">Discount:</td>
                        <td class="total-value" style="color: #dc2626;">- {{ number_format($sale->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if(($sale->round_off ?? 0) != 0)
                    <tr>
                        <td class="total-label">Round Off:</td>
                        <td class="total-value">{{ ($sale->round_off >= 0 ? '+' : '') . number_format($sale->round_off, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="total-label">Grand Total:</td>
                        <td class="total-value">&#8377; {{ number_format($sale->grand_total ?? 0, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ============================================================
         SIGNATURE AREA
         ============================================================ --}}
    <table class="signature-area">
        <tr>
            <td>
                <div class="sig-line">Customer Signature</div>
            </td>
            <td class="sig-right">
                <div class="sig-line">Authorized Signatory</div>
            </td>
        </tr>
    </table>

    {{-- ============================================================
         FOOTER
         ============================================================ --}}
    <div class="footer">
        <div class="thank-you">Thank you for your business!</div>
        <p>This is a computer-generated invoice and does not require a physical signature.</p>
        <p style="margin-top: 3pt;">Powered by MediStock Pro | Pharmacy Management System</p>
    </div>

</body>
</html>
