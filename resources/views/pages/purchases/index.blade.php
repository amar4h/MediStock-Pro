@extends('layouts.app')

@section('title', 'Purchases')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Purchases</span>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Purchases</h1>
            <p class="text-sm text-gray-500">Manage your purchase invoices and supplier orders</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Purchase
        </a>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="form-input"
                           placeholder="From Date">
                </div>
                <div class="flex-1">
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="form-input"
                           placeholder="To Date">
                </div>
                <div class="flex-1">
                    <select name="supplier_id" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach(($suppliers ?? []) as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <select name="payment_status" class="form-select">
                        <option value="">All Payment Status</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('purchases.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Purchase Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Supplier</th>
                        <th class="hidden sm:table-cell">Items</th>
                        <th class="text-right">Total</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($purchases ?? []) as $purchase)
                    <tr>
                        <td class="text-sm whitespace-nowrap">{{ \Carbon\Carbon::parse($purchase->invoice_date)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('purchases.show', $purchase->id) }}"
                               class="text-blue-600 hover:text-blue-700 font-medium">
                                {{ $purchase->invoice_number }}
                            </a>
                        </td>
                        <td class="truncate max-w-[150px]">{{ $purchase->supplier->name ?? $purchase->supplier_name ?? '-' }}</td>
                        <td class="hidden sm:table-cell">{{ $purchase->items_count ?? $purchase->items->count() ?? 0 }}</td>
                        <td class="text-right font-medium whitespace-nowrap">₹{{ number_format($purchase->grand_total, 2) }}</td>
                        <td>
                            @php
                                $paidAmt = $purchase->paid_amount ?? 0;
                                $totalAmt = $purchase->grand_total ?? 0;
                                $payStatus = $paidAmt >= $totalAmt ? 'paid' : ($paidAmt > 0 ? 'partial' : 'unpaid');
                            @endphp
                            @if($payStatus === 'paid')
                            <span class="badge-green">Paid</span>
                            @elseif($payStatus === 'partial')
                            <span class="badge-amber">Partial</span>
                            @else
                            <span class="badge-red">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('purchases.show', $purchase->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('purchases.edit', $purchase->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No purchases found</h3>
                            <p class="mt-1 text-sm text-gray-500">Record your first purchase to track inventory.</p>
                            <a href="{{ route('purchases.create') }}" class="btn-primary mt-4 inline-flex">
                                New Purchase
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($purchases ?? null) && method_exists($purchases, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} purchases
            </div>
            <div>{{ $purchases->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>

</div>
@endsection
