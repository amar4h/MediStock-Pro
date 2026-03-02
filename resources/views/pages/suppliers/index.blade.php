@extends('layouts.app')

@section('title', 'Suppliers')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Suppliers</span>
@endsection

@section('content')
<div x-data="{ showAddModal: false }" class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Suppliers</h1>
            <p class="text-sm text-gray-500">Manage your supplier contacts and payment records</p>
        </div>
        <button @click="showAddModal = true" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Supplier
        </button>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('suppliers.index') }}" class="flex gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-input pl-10"
                           placeholder="Search by name, phone, or GSTIN...">
                </div>
                <button type="submit" class="btn-primary">Search</button>
            </form>
        </div>
    </div>

    {{-- Supplier Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th class="hidden sm:table-cell">GSTIN</th>
                        <th class="hidden md:table-cell">Address</th>
                        <th class="text-right">Outstanding</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($suppliers ?? []) as $supplier)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full flex-shrink-0">
                                    <span class="text-sm font-semibold text-purple-700">{{ strtoupper(substr($supplier->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $supplier->name }}</p>
                                    <p class="text-xs text-gray-500 sm:hidden">{{ $supplier->gstin ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">
                            <a href="tel:{{ $supplier->phone }}" class="text-blue-600 hover:underline">{{ $supplier->phone ?? '-' }}</a>
                        </td>
                        <td class="hidden sm:table-cell text-sm font-mono text-gray-600">{{ $supplier->gstin ?? '-' }}</td>
                        <td class="hidden md:table-cell text-sm text-gray-600 truncate max-w-[180px]">{{ $supplier->address ?? '-' }}</td>
                        <td class="text-right">
                            @php $outstanding = $supplier->outstanding_balance ?? 0; @endphp
                            <span class="{{ $outstanding > 0 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                ₹{{ number_format($outstanding, 2) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-xs text-gray-400">{{ $supplier->created_at?->diffForHumans() ?? '-' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No suppliers yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Add your first supplier to start tracking purchases.</p>
                            <button @click="showAddModal = true" class="btn-primary mt-4 inline-flex">Add Supplier</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($suppliers ?? null) && method_exists($suppliers, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $suppliers->firstItem() ?? 0 }} to {{ $suppliers->lastItem() ?? 0 }} of {{ $suppliers->total() }}
            </div>
            <div>{{ $suppliers->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>

    {{-- Add Supplier Modal --}}
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <div x-show="showAddModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showAddModal = false"
             class="fixed inset-0 bg-black/50"></div>

        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
                <div x-show="showAddModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                     class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-xl">

                    <div class="sm:hidden flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
                    </div>

                    <div class="px-5 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Add Supplier</h3>
                    </div>

                    <form method="POST" action="{{ route('suppliers.store') }}" class="px-5 py-4 space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Company / Supplier Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="form-input" placeholder="Supplier name" required>
                        </div>
                        <div>
                            <label class="form-label">Phone <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" class="form-input" placeholder="Phone number" required>
                        </div>
                        <div>
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" class="form-input font-mono" placeholder="e.g., 22AAAAA0000A1Z5" maxlength="15">
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" placeholder="Email (optional)">
                        </div>
                        <div>
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="2" placeholder="Address (optional)"></textarea>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showAddModal = false" class="btn-secondary flex-1">Cancel</button>
                            <button type="submit" class="btn-primary flex-1">Save Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
