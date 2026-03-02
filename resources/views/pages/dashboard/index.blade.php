@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Quick Action Buttons --}}
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('sales.create') }}" class="btn-primary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Sale
        </a>
        <a href="{{ route('purchases.create') }}" class="btn-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
            New Purchase
        </a>
        <a href="{{ route('items.create') }}" class="btn-secondary">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Add Item
        </a>
    </div>

    {{-- Stat Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @include('components.stat-card', [
            'title'    => "Today's Sales",
            'value'    => '₹' . number_format($todaySales ?? 0, 2),
            'icon'     => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zM12 8V6m0 0a4 4 0 00-4 4v8a4 4 0 008 0v-8a4 4 0 00-4-4z',
            'color'    => 'green',
            'subtitle' => ($salesCount ?? 0) . ' invoices',
        ])

        @include('components.stat-card', [
            'title'    => "Today's Profit",
            'value'    => '₹' . number_format($todayProfit ?? 0, 2),
            'icon'     => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'color'    => 'blue',
            'subtitle' => 'Gross profit margin',
        ])

        @include('components.stat-card', [
            'title'    => 'Outstanding Credit',
            'value'    => '₹' . number_format($outstandingCredit ?? 0, 2),
            'icon'     => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            'color'    => 'amber',
            'subtitle' => ($creditCustomers ?? 0) . ' customers',
        ])

        @include('components.stat-card', [
            'title'    => 'Pending Payments',
            'value'    => '₹' . number_format($pendingPayments ?? 0, 2),
            'icon'     => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'color'    => 'red',
            'subtitle' => 'Supplier payments due',
        ])

        @include('components.stat-card', [
            'title'    => 'Low Stock Items',
            'value'    => $lowStockCount ?? 0,
            'icon'     => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color'    => 'purple',
            'subtitle' => 'Items below reorder level',
        ])

        @include('components.stat-card', [
            'title'    => 'Near Expiry',
            'value'    => $nearExpiryCount ?? 0,
            'icon'     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'color'    => 'red',
            'subtitle' => 'Batches expiring within 3 months',
        ])
    </div>

    {{-- Two Column Layout: Recent Sales + Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Sales (2/3 width on desktop) --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-semibold text-gray-900">Recent Sales</h3>
                        <a href="{{ route('sales.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            View All
                        </a>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th class="text-right">Total</th>
                                    <th>Payment</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentSales ?? []) as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale->id) }}"
                                           class="text-blue-600 hover:text-blue-700 font-medium">
                                            {{ $sale->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="truncate max-w-[120px]">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                    <td>{{ $sale->items_count ?? 0 }}</td>
                                    <td class="text-right font-medium">₹{{ number_format($sale->grand_total, 2) }}</td>
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
                                    <td class="text-gray-500 text-xs">{{ $sale->created_at->format('h:i A') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        No sales today yet. <a href="{{ route('sales.create') }}" class="text-blue-600 hover:underline">Create a sale</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alerts Panel (1/3 width on desktop) --}}
        <div class="space-y-4">

            {{-- Low Stock Alerts --}}
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                        <h3 class="text-sm font-semibold text-gray-900">Low Stock Alerts</h3>
                    </div>

                    <div class="space-y-2">
                        @forelse(($lowStockItems ?? []) as $item)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">Stock: {{ $item->current_stock ?? 0 }}</p>
                            </div>
                            <a href="{{ route('items.show', $item->id) }}"
                               class="text-xs text-blue-600 hover:underline flex-shrink-0 ml-2">
                                View
                            </a>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 py-2">No low stock alerts. All items are well stocked.</p>
                        @endforelse
                    </div>

                    @if(count($lowStockItems ?? []) > 0)
                    <a href="{{ route('inventory.index', ['tab' => 'low-stock']) }}"
                       class="block mt-3 text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                        View All Low Stock Items
                    </a>
                    @endif
                </div>
            </div>

            {{-- Near Expiry Alerts --}}
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                        <h3 class="text-sm font-semibold text-gray-900">Near Expiry Batches</h3>
                    </div>

                    <div class="space-y-2">
                        @forelse(($nearExpiryBatches ?? []) as $batch)
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $batch->item->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">
                                    Batch: {{ $batch->batch_number }} |
                                    Exp: <span class="text-red-600 font-medium">{{ \Carbon\Carbon::parse($batch->expiry_date)->format('M Y') }}</span>
                                </p>
                            </div>
                            <span class="badge-red flex-shrink-0 ml-2">{{ $batch->qty }} units</span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 py-2">No batches expiring soon.</p>
                        @endforelse
                    </div>

                    @if(count($nearExpiryBatches ?? []) > 0)
                    <a href="{{ route('inventory.index', ['tab' => 'near-expiry']) }}"
                       class="block mt-3 text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                        View All Near Expiry Batches
                    </a>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
