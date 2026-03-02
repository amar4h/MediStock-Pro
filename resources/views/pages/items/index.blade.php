@extends('layouts.app')

@section('title', 'Items')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Items</span>
@endsection

@section('content')
<div class="space-y-4">

    {{-- Header Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Item Master</h1>
            <p class="text-sm text-gray-500">Manage your medicine and product catalog</p>
        </div>
        <a href="{{ route('items.create') }}" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Item
        </a>
    </div>

    {{-- Search & Filters --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('items.index') }}" class="space-y-3">
                {{-- Search Bar --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search by name, composition, or barcode..."
                           class="form-input pl-10">
                </div>

                {{-- Filter Row --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <select name="category" class="form-select flex-1">
                        <option value="">All Categories</option>
                        @foreach(($categories ?? []) as $category)
                        <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                        @endforeach
                    </select>

                    <select name="manufacturer" class="form-select flex-1">
                        <option value="">All Manufacturers</option>
                        @foreach(($manufacturers ?? []) as $manufacturer)
                        <option value="{{ $manufacturer }}" {{ request('manufacturer') === $manufacturer ? 'selected' : '' }}>
                            {{ $manufacturer }}
                        </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select flex-1">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1 sm:flex-initial">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filter
                        </button>
                        <a href="{{ route('items.index') }}" class="btn-ghost flex-1 sm:flex-initial">
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => request('sort_by') === 'name' && request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}"
                               class="inline-flex items-center gap-1 hover:text-gray-700">
                                Name
                                @if(request('sort_by') === 'name')
                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    @if(request('sort_dir') === 'asc')
                                    <path d="M5.293 9.707l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 7.414l-3.293 3.293a1 1 0 01-1.414-1.414z"/>
                                    @else
                                    <path d="M14.707 10.293l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 12.586l3.293-3.293a1 1 0 111.414 1.414z"/>
                                    @endif
                                </svg>
                                @endif
                            </a>
                        </th>
                        <th class="hidden md:table-cell">Composition</th>
                        <th class="hidden sm:table-cell">Category</th>
                        <th class="hidden lg:table-cell">Manufacturer</th>
                        <th>GST%</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($items ?? []) as $item)
                    <tr>
                        <td>
                            <div>
                                <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                @if($item->barcode)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $item->barcode }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="hidden md:table-cell">
                            <p class="text-sm text-gray-600 truncate max-w-[200px]">{{ $item->composition ?? '-' }}</p>
                        </td>
                        <td class="hidden sm:table-cell">
                            <span class="badge-blue">{{ $item->category ?? '-' }}</span>
                        </td>
                        <td class="hidden lg:table-cell text-sm text-gray-600">{{ $item->manufacturer ?? '-' }}</td>
                        <td class="text-sm">{{ $item->gst_percent ?? 0 }}%</td>
                        <td>
                            @php $stock = $item->current_stock ?? 0; @endphp
                            <span class="{{ $stock <= 0 ? 'text-red-600 font-semibold' : ($stock <= ($item->reorder_level ?? 10) ? 'text-amber-600 font-semibold' : 'text-gray-900') }}">
                                {{ $stock }}
                            </span>
                        </td>
                        <td>
                            @if(($item->status ?? 'active') === 'active')
                            <span class="badge-green">Active</span>
                            @else
                            <span class="badge-gray">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('items.edit', $item->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('items.show', $item->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors"
                                   title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
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
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No items found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding your first item.</p>
                            <a href="{{ route('items.create') }}" class="btn-primary mt-4 inline-flex">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add First Item
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(is_object($items ?? null) && method_exists($items, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} items
            </div>
            <div>
                {{ $items->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
