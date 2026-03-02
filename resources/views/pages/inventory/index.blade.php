@extends('layouts.app')

@section('title', 'Inventory')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Inventory</span>
@endsection

@section('content')
<div x-data="{ activeTab: '{{ request('tab', 'summary') }}' }" class="space-y-4">

    {{-- Page Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900">Inventory Management</h1>
        <p class="text-sm text-gray-500">Monitor stock levels, expiry, and manage discards</p>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:shadow-md transition-shadow"
             @click="activeTab = 'summary'" :class="activeTab === 'summary' && 'ring-2 ring-blue-500'">
            <p class="text-xs text-gray-500">Total Items</p>
            <p class="text-xl font-bold text-gray-900">{{ $totalItems ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:shadow-md transition-shadow"
             @click="activeTab = 'low-stock'" :class="activeTab === 'low-stock' && 'ring-2 ring-amber-500'">
            <p class="text-xs text-gray-500">Low Stock</p>
            <p class="text-xl font-bold text-amber-600">{{ $lowStockCount ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:shadow-md transition-shadow"
             @click="activeTab = 'near-expiry'" :class="activeTab === 'near-expiry' && 'ring-2 ring-orange-500'">
            <p class="text-xs text-gray-500">Near Expiry</p>
            <p class="text-xl font-bold text-orange-600">{{ $nearExpiryCount ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:shadow-md transition-shadow"
             @click="activeTab = 'expired'" :class="activeTab === 'expired' && 'ring-2 ring-red-500'">
            <p class="text-xs text-gray-500">Expired</p>
            <p class="text-xl font-bold text-red-600">{{ $expiredCount ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 cursor-pointer hover:shadow-md transition-shadow"
             @click="activeTab = 'dead-stock'" :class="activeTab === 'dead-stock' && 'ring-2 ring-gray-500'">
            <p class="text-xs text-gray-500">Dead Stock</p>
            <p class="text-xl font-bold text-gray-600">{{ $deadStockCount ?? 0 }}</p>
        </div>
    </div>

    {{-- Tab Navigation (Mobile) --}}
    <div class="flex overflow-x-auto scrollbar-hidden -mx-4 px-4 sm:mx-0 sm:px-0 gap-1">
        <button @click="activeTab = 'summary'"
                :class="activeTab === 'summary' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Stock Summary
        </button>
        <button @click="activeTab = 'low-stock'"
                :class="activeTab === 'low-stock' ? 'bg-amber-500 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Low Stock
        </button>
        <button @click="activeTab = 'near-expiry'"
                :class="activeTab === 'near-expiry' ? 'bg-orange-500 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Near Expiry
        </button>
        <button @click="activeTab = 'expired'"
                :class="activeTab === 'expired' ? 'bg-red-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Expired
        </button>
        <button @click="activeTab = 'dead-stock'"
                :class="activeTab === 'dead-stock' ? 'bg-gray-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Dead Stock
        </button>
    </div>

    {{-- TAB: Stock Summary --}}
    <div x-show="activeTab === 'summary'" x-cloak>
        <div class="card">
            <div class="card-body py-3">
                <input type="text" placeholder="Search items by name..." class="form-input"
                       x-data @input.debounce.300ms="$dispatch('search-inventory', $el.value)">
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th class="hidden sm:table-cell">Category</th>
                            <th class="text-right">Total Stock</th>
                            <th class="text-right hidden sm:table-cell">Stock Value</th>
                            <th class="hidden md:table-cell">Batches</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($stockSummary ?? []) as $item)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500 sm:hidden">{{ $item->category->name ?? '' }}</p>
                            </td>
                            <td class="hidden sm:table-cell"><span class="badge-blue">{{ $item->category->name ?? '-' }}</span></td>
                            <td class="text-right font-medium">{{ $item->current_stock ?? 0 }}</td>
                            <td class="text-right hidden sm:table-cell">₹{{ number_format($item->stock_value ?? 0, 2) }}</td>
                            <td class="hidden md:table-cell">{{ $item->batches_count ?? 0 }}</td>
                            <td>
                                @php $stock = $item->current_stock ?? 0; $reorder = $item->reorder_level ?? 10; @endphp
                                @if($stock <= 0)
                                <span class="badge-red">Out of Stock</span>
                                @elseif($stock <= $reorder)
                                <span class="badge-amber">Low Stock</span>
                                @else
                                <span class="badge-green">In Stock</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">No inventory data available.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Low Stock --}}
    <div x-show="activeTab === 'low-stock'" x-cloak>
        <div class="card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th class="text-right">Current Stock</th>
                            <th class="text-right">Reorder Level</th>
                            <th class="hidden sm:table-cell">Last Purchase</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($lowStockItems ?? []) as $item)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->manufacturer->name ?? '' }}</p>
                            </td>
                            <td class="text-right">
                                <span class="font-semibold {{ ($item->current_stock ?? 0) <= 0 ? 'text-red-600' : 'text-amber-600' }}">
                                    {{ $item->current_stock ?? 0 }}
                                </span>
                            </td>
                            <td class="text-right text-gray-600">{{ $item->reorder_level ?? 10 }}</td>
                            <td class="hidden sm:table-cell text-sm text-gray-500">{{ $item->last_purchase_date ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('purchases.create', ['item_id' => $item->id]) }}" class="btn-primary btn-sm">
                                    Reorder
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No low stock items. Everything is well stocked!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Near Expiry --}}
    <div x-show="activeTab === 'near-expiry'" x-cloak>
        <div class="card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right hidden sm:table-cell">Value</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($nearExpiryBatches ?? []) as $batch)
                        <tr>
                            <td class="font-medium text-gray-900">{{ $batch->item->name ?? 'Unknown' }}</td>
                            <td class="text-sm">{{ $batch->batch_number }}</td>
                            <td>
                                <span class="text-orange-600 font-medium">
                                    {{ \Carbon\Carbon::parse($batch->expiry_date)->format('M Y') }}
                                </span>
                            </td>
                            <td class="text-right">{{ $batch->qty ?? 0 }}</td>
                            <td class="text-right hidden sm:table-cell">₹{{ number_format(($batch->qty ?? 0) * ($batch->purchase_price ?? 0), 2) }}</td>
                            <td>
                                <button @click="$dispatch('open-modal', 'discard-stock')"
                                        class="btn-warning btn-sm">
                                    Discard
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">No batches near expiry.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Expired --}}
    <div x-show="activeTab === 'expired'" x-cloak>
        <div class="card">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Batch #</th>
                            <th>Expired On</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right hidden sm:table-cell">Loss Value</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($expiredBatches ?? []) as $batch)
                        <tr class="bg-red-50/50">
                            <td class="font-medium text-gray-900">{{ $batch->item->name ?? 'Unknown' }}</td>
                            <td class="text-sm">{{ $batch->batch_number }}</td>
                            <td><span class="text-red-600 font-medium">{{ \Carbon\Carbon::parse($batch->expiry_date)->format('M Y') }}</span></td>
                            <td class="text-right">{{ $batch->qty ?? 0 }}</td>
                            <td class="text-right hidden sm:table-cell text-red-600 font-medium">₹{{ number_format(($batch->qty ?? 0) * ($batch->purchase_price ?? 0), 2) }}</td>
                            <td>
                                <button @click="$dispatch('open-modal', 'discard-stock')"
                                        class="btn-danger btn-sm">
                                    Discard
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-500">No expired batches.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: Dead Stock --}}
    <div x-show="activeTab === 'dead-stock'" x-cloak>
        <div class="card">
            <div class="card-body py-3 border-b border-gray-200">
                <p class="text-sm text-gray-500">Items with no sales in the last 90 days</p>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Stock</th>
                            <th class="text-right hidden sm:table-cell">Value</th>
                            <th class="hidden sm:table-cell">Last Sold</th>
                            <th class="hidden md:table-cell">Days Idle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($deadStockItems ?? []) as $item)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item->manufacturer->name ?? '' }}</p>
                            </td>
                            <td class="text-right">{{ $item->current_stock ?? 0 }}</td>
                            <td class="text-right hidden sm:table-cell">₹{{ number_format($item->stock_value ?? 0, 2) }}</td>
                            <td class="hidden sm:table-cell text-sm text-gray-500">{{ $item->last_sold_date ?? 'Never' }}</td>
                            <td class="hidden md:table-cell text-sm text-gray-500">{{ $item->days_idle ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">No dead stock items found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Discard Stock Modal --}}
    @include('components.modal', ['name' => 'discard-stock', 'title' => 'Discard Stock', 'maxWidth' => 'md'])

</div>
@endsection
