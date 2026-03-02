<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): View
    {
        $sales = Sale::with(['customer:id,name,phone', 'createdBy:id,name'])
            ->withCount('saleItems')
            ->when($request->filled('date_from'), fn ($q) => $q->where('invoice_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('invoice_date', '<=', $request->input('date_to')))
            ->when($request->filled('payment_mode'), fn ($q) => $q->where('payment_mode', $request->input('payment_mode')))
            ->when($request->filled('customer'), function ($q) use ($request) {
                $term = $request->input('customer');
                $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"));
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Summary aggregations
        $summaryQuery = Sale::query()
            ->when($request->filled('date_from'), fn ($q) => $q->where('invoice_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('invoice_date', '<=', $request->input('date_to')))
            ->when($request->filled('payment_mode'), fn ($q) => $q->where('payment_mode', $request->input('payment_mode')));

        $totalSalesAmount  = (clone $summaryQuery)->sum('total_amount');
        $totalInvoices     = (clone $summaryQuery)->count();
        $cashCollected     = (clone $summaryQuery)->where('payment_mode', 'cash')->sum('total_amount');
        $creditOutstanding = (clone $summaryQuery)->sum('balance_amount');

        return view('pages.sales.index', compact(
            'sales', 'totalSalesAmount', 'totalInvoices', 'cashCollected', 'creditOutstanding'
        ));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get(['id', 'name', 'phone']);

        return view('pages.sales.create', compact('customers'));
    }

    public function show(Sale $sale): View
    {
        $sale->load([
            'customer',
            'saleItems.item:id,name,unit,composition',
            'saleItems.batch:id,batch_number,expiry_date,mrp',
            'createdBy:id,name',
        ]);

        return view('pages.sales.show', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load(['customer', 'saleItems.item', 'saleItems.batch']);

        $pdf = $this->invoiceService->generateInvoicePdf($sale);

        return $pdf->stream("invoice-{$sale->invoice_number}.pdf");
    }
}
