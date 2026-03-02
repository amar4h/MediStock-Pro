@extends('layouts.app')

@section('title', 'New Purchase')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<a href="{{ route('purchases.index') }}" class="hover:text-blue-600">Purchases</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">New Purchase</span>
@endsection

@section('content')
<div x-data="purchaseForm()" class="space-y-6 max-w-6xl mx-auto">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">New Purchase Entry</h1>
            <p class="text-sm text-gray-500 mt-1">Record a purchase invoice from your supplier</p>
        </div>
    </div>

    {{-- Invoice Scanner --}}
    @include('components.invoice-scanner')

    {{-- Scan Prefill Indicator --}}
    <template x-if="scanPrefilled">
        <div class="rounded-lg bg-purple-50 border border-purple-200 px-4 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-purple-800">
                Fields prefilled from invoice scan. Color-coded borders indicate confidence level. Please verify all values before saving.
            </p>
        </div>
    </template>

    {{-- Supplier & Invoice Details --}}
    <div class="card">
        <div class="card-body space-y-4">
            <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Invoice Details</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Supplier --}}
                <div class="sm:col-span-2">
                    <label class="form-label">Supplier <span class="text-red-500">*</span></label>
                    <div class="relative" x-data="{ supplierSearch: '', supplierOpen: false, suppliers: {{ json_encode($suppliers ?? []) }} }">
                        <input type="text"
                               x-model="supplierSearch"
                               @focus="supplierOpen = true"
                               @click.away="supplierOpen = false"
                               :class="fieldConfidenceClass('supplier_name')"
                               class="form-input"
                               placeholder="Search supplier by name..."
                               autocomplete="off">
                        <input type="hidden" name="supplier_id" x-model="supplierId">

                        <div x-show="supplierOpen && supplierSearch.length > 0"
                             x-cloak
                             class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                            <template x-for="supplier in suppliers.filter(s => s.name.toLowerCase().includes(supplierSearch.toLowerCase()))" :key="supplier.id">
                                <button @click="supplierId = supplier.id; supplierName = supplier.name; supplierSearch = supplier.name; supplierGstin = supplier.gstin || ''; supplierOpen = false"
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-sm transition-colors">
                                    <span class="font-medium" x-text="supplier.name"></span>
                                    <span class="text-gray-400 text-xs ml-2" x-text="supplier.gstin || ''"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Invoice Number --}}
                <div>
                    <label class="form-label">Invoice Number <span class="text-red-500">*</span></label>
                    <input type="text"
                           x-model="invoiceNumber"
                           :class="fieldConfidenceClass('invoice_number')"
                           class="form-input"
                           placeholder="e.g., INV-2024-001"
                           required>
                </div>

                {{-- Invoice Date --}}
                <div>
                    <label class="form-label">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date"
                           x-model="invoiceDate"
                           :class="fieldConfidenceClass('invoice_date')"
                           class="form-input"
                           required>
                </div>
            </div>
        </div>
    </div>

    {{-- Purchase Items Table --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Items</h2>
                <button @click="addRow()" type="button" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Row
                </button>
            </div>

            {{-- Items Table (scrollable on mobile) --}}
            <div class="overflow-x-auto -mx-4 sm:mx-0 custom-scrollbar">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-[200px]">Item</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-[90px]">Batch #</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold text-gray-500 uppercase w-[100px]">Expiry</th>
                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-[55px]">Qty</th>
                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-[50px]">Free</th>
                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-[75px]">MRP</th>
                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-[80px]">Pur. Price</th>
                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-[80px]">Sell Price</th>
                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-[55px]">GST%</th>
                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-[55px]">Disc%</th>
                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase w-[80px]">Amount</th>
                            <th class="px-2 py-2 w-[40px]"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                {{-- Item Name (searchable) --}}
                                <td class="px-2 py-1.5">
                                    <div class="relative">
                                        <input type="text"
                                               x-model="item.itemName"
                                               @input.debounce.300ms="searchItemsForRow(index, item.itemName)"
                                               @focus="activeRowIndex = index"
                                               :class="rowFieldConfidenceClass(index, 'name')"
                                               class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                               placeholder="Search item...">

                                        <div x-show="searchOpen && activeRowIndex === index"
                                             x-cloak
                                             @click.away="searchOpen = false"
                                             class="absolute z-30 w-64 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                            <template x-for="result in searchResults" :key="result.id">
                                                <button @click="selectItemForRow(result)"
                                                        type="button"
                                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm transition-colors">
                                                    <span class="font-medium" x-text="result.name"></span>
                                                    <span class="text-gray-400 text-xs block" x-text="result.composition || ''"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                {{-- Batch # --}}
                                <td class="px-2 py-1.5">
                                    <input type="text" x-model="item.batchNumber"
                                           :class="rowFieldConfidenceClass(index, 'batch_number')"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="Batch">
                                </td>
                                {{-- Expiry --}}
                                <td class="px-2 py-1.5">
                                    <input type="month" x-model="item.expiry"
                                           :class="rowFieldConfidenceClass(index, 'expiry')"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20">
                                </td>
                                {{-- Qty --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.qty" min="0"
                                           :class="rowFieldConfidenceClass(index, 'qty')"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-center focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0">
                                </td>
                                {{-- Free Qty --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.freeQty" min="0"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-center focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0">
                                </td>
                                {{-- MRP --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.mrp" step="0.01" min="0"
                                           :class="rowFieldConfidenceClass(index, 'mrp')"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0.00">
                                </td>
                                {{-- Purchase Price --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.purchasePrice" step="0.01" min="0"
                                           :class="rowFieldConfidenceClass(index, 'purchase_price')"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0.00">
                                </td>
                                {{-- Selling Price --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.sellingPrice" step="0.01" min="0"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0.00">
                                </td>
                                {{-- GST% --}}
                                <td class="px-2 py-1.5">
                                    <select x-model="item.gstPercent"
                                            class="w-full px-1 py-1.5 text-sm border border-gray-300 rounded-md text-center focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20">
                                        <option value="0">0%</option>
                                        <option value="5">5%</option>
                                        <option value="12">12%</option>
                                        <option value="18">18%</option>
                                        <option value="28">28%</option>
                                    </select>
                                </td>
                                {{-- Disc% --}}
                                <td class="px-2 py-1.5">
                                    <input type="number" x-model.number="item.discountPercent" step="0.01" min="0" max="100"
                                           class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md text-center focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                                           placeholder="0">
                                </td>
                                {{-- Amount --}}
                                <td class="px-2 py-1.5 text-right font-medium text-gray-900" x-text="formatNumber(calculateRowAmount(index))">
                                    0.00
                                </td>
                                {{-- Remove --}}
                                <td class="px-2 py-1.5">
                                    <button @click="removeRow(index)" type="button"
                                            class="p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                            title="Remove row">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Add Row Button (bottom) --}}
            <div class="mt-3">
                <button @click="addRow()" type="button" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    + Add another item
                </button>
            </div>
        </div>
    </div>

    {{-- Totals & Payment --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Payment Details --}}
        <div class="card">
            <div class="card-body space-y-4">
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Payment</h2>

                {{-- Payment Mode --}}
                <div>
                    <label class="form-label">Payment Mode</label>
                    <div class="flex gap-2 mt-1">
                        <button @click="setPaymentMode('cash')" type="button"
                                :class="paymentMode === 'cash' ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="btn border flex-1">
                            Cash
                        </button>
                        <button @click="setPaymentMode('credit')" type="button"
                                :class="paymentMode === 'credit' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="btn border flex-1">
                            Credit
                        </button>
                        <button @click="setPaymentMode('partial')" type="button"
                                :class="paymentMode === 'partial' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="btn border flex-1">
                            Partial
                        </button>
                    </div>
                </div>

                {{-- Paid Amount --}}
                <div>
                    <label class="form-label">Paid Amount</label>
                    <input type="number"
                           x-model.number="paidAmount"
                           step="0.01"
                           min="0"
                           class="form-input"
                           :disabled="paymentMode === 'credit'"
                           placeholder="0.00">
                </div>

                {{-- Balance --}}
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <span class="text-sm font-medium text-gray-600">Balance Due</span>
                    <span class="text-lg font-bold" :class="balanceAmount > 0 ? 'text-red-600' : 'text-green-600'"
                          x-text="formatCurrency(balanceAmount)">
                        ₹0.00
                    </span>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="form-label">Notes (Optional)</label>
                    <textarea x-model="notes"
                              class="form-input"
                              rows="2"
                              placeholder="Any additional notes..."></textarea>
                </div>
            </div>
        </div>

        {{-- Totals Summary --}}
        <div class="card">
            <div class="card-body">
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">Summary</h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium text-gray-900" x-text="formatCurrency(subtotal)">₹0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Total Discount</span>
                        <span class="font-medium text-red-600" x-text="'- ' + formatCurrency(totalDiscount)">- ₹0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">GST Amount</span>
                        <span class="font-medium text-gray-900" x-text="formatCurrency(totalGst)">₹0.00</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-semibold text-gray-900">Grand Total</span>
                            <span class="text-xl font-bold text-blue-600" x-text="formatCurrency(grandTotal)">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:justify-end pb-6">
        <a href="{{ route('purchases.index') }}" class="btn-secondary w-full sm:w-auto">
            Cancel
        </a>
        <button @click="submitPurchase()"
                :disabled="!canSubmit"
                type="button"
                class="btn-primary w-full sm:w-auto">
            <template x-if="!submitting">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Purchase
                </span>
            </template>
            <template x-if="submitting">
                <span class="flex items-center gap-2">
                    <span class="spinner spinner-sm"></span>
                    Saving...
                </span>
            </template>
        </button>
    </div>

</div>
@endsection
