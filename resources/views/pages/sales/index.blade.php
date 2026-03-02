@extends('layouts.app')

@section('title', 'Sales')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Sales</span>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Sales</h1>
            <p class="text-sm text-gray-500">View and manage all sales transactions</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Sale
        </a>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" placeholder="From Date">
                </div>
                <div class="flex-1">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" placeholder="To Date">
                </div>
                <div class="flex-1">
                    <input type="text" name="customer" value="{{ request('customer') }}" class="form-input" placeholder="Customer name or phone...">
                </div>
                <div class="flex-1">
                    <select name="payment_mode" class="form-select">
                        <option value="">All Payments</option>
                        <option value="cash" {{ request('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="credit" {{ request('payment_mode') === 'credit' ? 'selected' : '' }}>Credit</option>
                        <option value="upi" {{ request('payment_mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                        <option value="partial" {{ request('payment_mode') === 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('sales.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Total Sales</p>
            <p class="text-lg font-bold text-gray-900">₹{{ number_format($totalSalesAmount ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Invoices</p>
            <p class="text-lg font-bold text-gray-900">{{ $totalInvoices ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Cash Collected</p>
            <p class="text-lg font-bold text-green-600">₹{{ number_format($cashCollected ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Credit Outstanding</p>
            <p class="text-lg font-bold text-amber-600">₹{{ number_format($creditOutstanding ?? 0, 2) }}</p>
        </div>
    </div>

    {{-- Sales Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th class="hidden sm:table-cell">Items</th>
                        <th class="text-right">Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($sales ?? []) as $sale)
                    <tr>
                        <td class="text-sm whitespace-nowrap">{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('sales.show', $sale->id) }}"
                               class="text-blue-600 hover:text-blue-700 font-medium">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td class="truncate max-w-[120px]">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                        <td class="hidden sm:table-cell">{{ $sale->items_count ?? 0 }}</td>
                        <td class="text-right font-medium whitespace-nowrap">₹{{ number_format($sale->grand_total, 2) }}</td>
                        <td>
                            @if($sale->payment_mode === 'cash')
                            <span class="badge-green">Cash</span>
                            @elseif($sale->payment_mode === 'credit')
                            <span class="badge-amber">Credit</span>
                            @elseif($sale->payment_mode === 'upi')
                            <span class="badge-blue">UPI</span>
                            @else
                            <span class="badge-gray">{{ ucfirst($sale->payment_mode) }}</span>
                            @endif
                        </td>
                        <td>
                            @if(($sale->status ?? 'completed') === 'completed')
                            <span class="badge-green">Completed</span>
                            @elseif(($sale->status ?? '') === 'returned')
                            <span class="badge-red">Returned</span>
                            @else
                            <span class="badge-gray">{{ ucfirst($sale->status ?? 'N/A') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('sales.show', $sale->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ url('/sales/' . $sale->id . '/invoice') }}"
                                   target="_blank"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                                   title="Print Invoice">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12">
                            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No sales found</h3>
                            <p class="mt-1 text-sm text-gray-500">Start billing to see your sales here.</p>
                            <a href="{{ route('sales.create') }}" class="btn-primary mt-4 inline-flex">New Sale</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($sales ?? null) && method_exists($sales, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $sales->firstItem() ?? 0 }} to {{ $sales->lastItem() ?? 0 }} of {{ $sales->total() }} sales
            </div>
            <div>{{ $sales->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>

</div>
@endsection
