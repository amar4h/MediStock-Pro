<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = Purchase::with(['supplier:id,name', 'createdBy:id,name'])
            ->withCount('purchaseItems')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->input('supplier_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('invoice_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('invoice_date', '<=', $request->input('date_to')))
            ->when($request->filled('payment_status'), function ($q) use ($request) {
                match ($request->input('payment_status')) {
                    'paid'    => $q->whereRaw('paid_amount >= total_amount'),
                    'partial' => $q->whereRaw('paid_amount > 0 AND paid_amount < total_amount'),
                    'unpaid'  => $q->where('paid_amount', 0),
                    default   => null,
                };
            })
            ->orderByDesc('invoice_date')
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('pages.purchases.index', compact('purchases', 'suppliers'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'gstin']);

        return view('pages.purchases.create', compact('suppliers'));
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load([
            'supplier',
            'purchaseItems.item:id,name,unit',
            'purchaseItems.batch',
            'createdBy:id,name',
        ]);

        return view('pages.purchases.show', compact('purchase'));
    }
}
