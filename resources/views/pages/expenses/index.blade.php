@extends('layouts.app')

@section('title', 'Expenses')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Expenses</span>
@endsection

@section('content')
<div x-data="{ showAddModal: false }" class="space-y-4">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Expenses</h1>
            <p class="text-sm text-gray-500">Track and categorize your store expenses</p>
        </div>
        <button @click="showAddModal = true" class="btn-primary w-full sm:w-auto">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Expense
        </button>
    </div>

    {{-- Monthly Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">This Month</p>
            <p class="text-xl font-bold text-red-600">₹{{ number_format($monthlyTotal ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Last Month</p>
            <p class="text-xl font-bold text-gray-900">₹{{ number_format($lastMonthTotal ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Transactions</p>
            <p class="text-xl font-bold text-gray-900">{{ $transactionCount ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-500">Top Category</p>
            <p class="text-lg font-bold text-gray-900 truncate">{{ $topCategory ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input" placeholder="From">
                </div>
                <div class="flex-1">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input" placeholder="To">
                </div>
                <div class="flex-1">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(($categories ?? ['Rent', 'Electricity', 'Salary', 'Transport', 'Repairs', 'Stationary', 'Marketing', 'Insurance', 'Other']) as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <select name="payment_mode" class="form-select">
                        <option value="">All Payment Modes</option>
                        <option value="cash" {{ request('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank" {{ request('payment_mode') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="upi" {{ request('payment_mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                        <option value="card" {{ request('payment_mode') === 'card' ? 'selected' : '' }}>Card</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="{{ route('expenses.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Expense Table --}}
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                        <th class="hidden sm:table-cell">Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($expenses ?? []) as $expense)
                    <tr>
                        <td class="text-sm whitespace-nowrap">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                        <td>
                            @php
                                $catColors = [
                                    'Rent' => 'badge-blue',
                                    'Electricity' => 'badge-amber',
                                    'Salary' => 'badge-green',
                                    'Transport' => 'badge-gray',
                                ];
                            @endphp
                            <span class="{{ $catColors[$expense->category] ?? 'badge-gray' }}">{{ $expense->category }}</span>
                        </td>
                        <td class="text-sm text-gray-700 truncate max-w-[200px]">{{ $expense->description ?? '-' }}</td>
                        <td class="text-right font-semibold text-red-600 whitespace-nowrap">₹{{ number_format($expense->amount, 2) }}</td>
                        <td class="hidden sm:table-cell text-sm text-gray-600">{{ ucfirst($expense->payment_mode ?? 'cash') }}</td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('expenses.edit', $expense->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('expenses.destroy', $expense->id) }}"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12">
                            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <h3 class="mt-3 text-sm font-medium text-gray-900">No expenses recorded</h3>
                            <p class="mt-1 text-sm text-gray-500">Start tracking your store expenses.</p>
                            <button @click="showAddModal = true" class="btn-primary mt-4 inline-flex">Add Expense</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($expenses ?? null) && method_exists($expenses, 'links'))
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-2 sm:mb-0">
                Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }}
            </div>
            <div>{{ $expenses->withQueryString()->links() }}</div>
        </div>
        @endif
    </div>

    {{-- Add Expense Modal --}}
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
                        <h3 class="text-lg font-semibold text-gray-900">Add Expense</h3>
                    </div>

                    <form method="POST" action="{{ route('expenses.store') }}" class="px-5 py-4 space-y-4">
                        @csrf
                        <div>
                            <label class="form-label">Date <span class="text-red-500">*</span></label>
                            <input type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Category <span class="text-red-500">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach(['Rent', 'Electricity', 'Salary', 'Transport', 'Repairs', 'Stationary', 'Marketing', 'Insurance', 'Other'] as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-input" placeholder="Brief description">
                        </div>
                        <div>
                            <label class="form-label">Amount <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" class="form-input" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div>
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="button" @click="showAddModal = false" class="btn-secondary flex-1">Cancel</button>
                            <button type="submit" class="btn-primary flex-1">Save Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
