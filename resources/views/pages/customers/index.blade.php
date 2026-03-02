@extends('layouts.app')

@section('title', 'Customers')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Customers</span>
@endsection

@section('content')
<div x-data="{ showAddModal: false }" class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Customers</h1>
            <p class="text-sm text-gray-500">Manage your customer records and credit balances</p>
        </div>
        <button @click="showAddModal = true" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Customer
        </button>
    </div>

    {{-- Search --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('customers.index') }}" class="flex gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-input pl-10"
                           placeholder="Search by name or phone...">
                </div>
                <button type="submit" class="btn-primary">Search</button>
            </form>
        </div>
    </div>

    {{-- Customer Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th class="hidden sm:table-cell">Address</th>
                        <th class="text-right">Outstanding Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($customers ?? []) as $customer)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full flex-shrink-0">
                                    <span class="text-sm font-semibold text-blue-700">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                </div>
                                <span class="font-medium text-gray-900">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td class="text-sm">
                            <a href="tel:{{ $customer->phone }}" class="text-blue-600 hover:underline">{{ $customer->phone ?? '-' }}</a>
                        </td>
                        <td class="hidden sm:table-cell text-sm text-gray-600 truncate max-w-[200px]">{{ $customer->address ?? '-' }}</td>
                        <td class="text-right">
                            @php $balance = $customer->outstanding_balance ?? 0; @endphp
                            <span class="{{ $balance > 0 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                ₹{{ number_format($balance, 2) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('customers.show', $customer->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="View History">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <a href="{{ route('customers.edit', $customer->id) }}"
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
                        <td colspan="5" class="text-center py-12">
                            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No customers yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Add your first customer to track their purchases.</p>
                            <button @click="showAddModal = true" class="btn-primary mt-4 inline-flex">Add Customer</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($customers ?? null) && method_exists($customers, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }}
            </div>
            <div>{{ $customers->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>

    {{-- Add Customer Modal --}}
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
                        <h3 class="text-lg font-semibold text-gray-900">Add Customer</h3>
                    </div>

                    <form method="POST" action="{{ route('customers.store') }}" class="px-5 py-4 space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" class="form-input" placeholder="Customer name" required>
                        </div>
                        <div>
                            <label class="form-label">Phone <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" class="form-input" placeholder="Phone number" required>
                        </div>
                        <div>
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="2" placeholder="Address (optional)"></textarea>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showAddModal = false" class="btn-secondary flex-1">Cancel</button>
                            <button type="submit" class="btn-primary flex-1">Save Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
