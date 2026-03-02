@extends('layouts.app')

@section('title', 'New Sale')

@section('content')
<div x-data="posBilling()" class="h-full">

    {{-- Sale Completed Overlay --}}
    <div x-show="saleCompleted" x-cloak
         class="fixed inset-0 z-50 bg-white/95 flex items-center justify-center p-4">
        <div class="text-center max-w-sm">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">Sale Completed!</h2>
            <p class="text-gray-600 mt-2">Invoice: <span class="font-semibold" x-text="saleInvoiceNumber"></span></p>
            <p class="text-gray-500 text-sm mt-1">Total: <span class="font-bold text-lg text-green-600" x-text="formatCurrency(grandTotal)"></span></p>

            <div class="flex flex-col gap-3 mt-6">
                <button @click="printInvoice()" class="btn-primary w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Invoice
                </button>
                <button @click="newSale()" class="btn-success w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New Sale
                </button>
                <a href="{{ route('sales.index') }}" class="btn-secondary w-full">
                    Back to Sales
                </a>
            </div>
        </div>
    </div>

    {{-- POS Layout --}}
    <div class="flex flex-col lg:flex-row gap-4 h-full">

        {{-- LEFT: Items Panel --}}
        <div class="flex-1 flex flex-col min-w-0 space-y-4">

            {{-- Barcode Input --}}
            <div class="card">
                <div class="card-body py-3">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   x-model="barcodeInput"
                                   @keydown.enter.prevent="handleBarcodeSubmit()"
                                   class="form-input pl-10 text-lg font-mono"
                                   placeholder="Scan barcode or enter code..."
                                   autofocus>
                        </div>
                        <button @click="handleBarcodeSubmit()" class="btn-primary px-6">
                            Add
                        </button>
                    </div>
                </div>
            </div>

            {{-- Item Search --}}
            <div class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchQuery"
                           @input.debounce.300ms="searchItems()"
                           @focus="if (searchResults.length) searchOpen = true"
                           class="form-input pl-10"
                           placeholder="Search item by name...">
                </div>

                {{-- Search Dropdown --}}
                <div x-show="searchOpen" x-cloak @click.away="searchOpen = false"
                     class="absolute z-30 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto">
                    <template x-for="result in searchResults" :key="result.item_id + '-' + result.batch_id">
                        <button @click="addItem(result); searchOpen = false"
                                type="button"
                                class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm" x-text="result.name"></p>
                                    <p class="text-xs text-gray-500">
                                        Batch: <span x-text="result.batch_number || 'N/A'"></span> |
                                        Exp: <span x-text="result.expiry || 'N/A'"></span> |
                                        Stock: <span x-text="result.available_qty"></span>
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0 ml-3">
                                    <p class="font-semibold text-gray-900 text-sm">₹<span x-text="result.selling_price"></span></p>
                                    <p class="text-xs text-gray-400">MRP: ₹<span x-text="result.mrp"></span></p>
                                </div>
                            </div>
                        </button>
                    </template>
                    <template x-if="searchResults.length === 0 && searchQuery.length >= 2">
                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                            No items found for "<span x-text="searchQuery"></span>"
                        </div>
                    </template>
                </div>
            </div>

            {{-- Items List --}}
            <div class="card flex-1 flex flex-col min-h-0">
                <div class="card-body flex-1 overflow-y-auto custom-scrollbar p-0">
                    {{-- Items Header --}}
                    <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-4 py-2 hidden sm:grid sm:grid-cols-12 gap-2 text-xs font-semibold text-gray-500 uppercase">
                        <div class="col-span-4">Item</div>
                        <div class="col-span-2 text-center">Qty</div>
                        <div class="col-span-2 text-right">Price</div>
                        <div class="col-span-1 text-center">Disc%</div>
                        <div class="col-span-2 text-right">Amount</div>
                        <div class="col-span-1"></div>
                    </div>

                    {{-- Item Rows --}}
                    <template x-if="items.length === 0">
                        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                            <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                            <p class="text-sm font-medium">No items added</p>
                            <p class="text-xs mt-1">Scan a barcode or search for items</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in items" :key="index">
                        <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            {{-- Mobile Layout --}}
                            <div class="sm:hidden space-y-2">
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-gray-900 text-sm truncate" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500">
                                            Batch: <span x-text="item.batchNumber || 'N/A'"></span>
                                            <template x-if="item.schedule">
                                                <span class="ml-1 text-red-500" x-text="'[' + item.schedule + ']'"></span>
                                            </template>
                                        </p>
                                    </div>
                                    <button @click="removeItem(index)"
                                            class="p-1 rounded text-gray-400 hover:text-red-500 ml-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button @click="updateItemQty(index, item.qty - 1)"
                                                class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            -
                                        </button>
                                        <input type="number" x-model.number="item.qty" min="1" :max="item.maxQty"
                                               @change="updateItemQty(index, item.qty)"
                                               class="w-14 h-8 text-center text-sm border border-gray-300 rounded-lg">
                                        <button @click="updateItemQty(index, item.qty + 1)"
                                                class="w-8 h-8 rounded-lg border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            +
                                        </button>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900" x-text="formatCurrency(item.qty * item.price * (1 - item.discount/100))"></p>
                                        <p class="text-xs text-gray-500">₹<span x-text="item.price"></span> each</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Desktop Layout --}}
                            <div class="hidden sm:grid sm:grid-cols-12 gap-2 items-center">
                                <div class="col-span-4">
                                    <p class="font-medium text-gray-900 text-sm truncate" x-text="item.name"></p>
                                    <p class="text-xs text-gray-500">
                                        <span x-text="item.batchNumber || ''"></span>
                                        <template x-if="item.expiry">
                                            <span> | Exp: <span x-text="item.expiry"></span></span>
                                        </template>
                                    </p>
                                </div>
                                <div class="col-span-2 flex items-center justify-center gap-1">
                                    <button @click="updateItemQty(index, item.qty - 1)"
                                            class="w-7 h-7 rounded border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100 text-sm">-</button>
                                    <input type="number" x-model.number="item.qty" min="1" :max="item.maxQty"
                                           @change="updateItemQty(index, item.qty)"
                                           class="w-12 h-7 text-center text-sm border border-gray-300 rounded">
                                    <button @click="updateItemQty(index, item.qty + 1)"
                                            class="w-7 h-7 rounded border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-100 text-sm">+</button>
                                </div>
                                <div class="col-span-2 text-right text-sm text-gray-700" x-text="'₹' + formatNumber(item.price)"></div>
                                <div class="col-span-1">
                                    <input type="number" x-model.number="item.discount" min="0" max="100" step="0.5"
                                           class="w-full h-7 text-center text-xs border border-gray-300 rounded"
                                           placeholder="0">
                                </div>
                                <div class="col-span-2 text-right font-semibold text-gray-900 text-sm"
                                     x-text="formatCurrency(item.qty * item.price * (1 - item.discount/100))"></div>
                                <div class="col-span-1 text-right">
                                    <button @click="removeItem(index)"
                                            class="p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Items Footer --}}
                <div class="border-t border-gray-200 px-4 py-2 bg-gray-50 flex items-center justify-between text-sm">
                    <span class="text-gray-500"><span x-text="items.length"></span> items (<span x-text="itemCount"></span> units)</span>
                    <span class="font-semibold text-gray-900">Subtotal: <span x-text="formatCurrency(subtotal)"></span></span>
                </div>
            </div>
        </div>

        {{-- RIGHT: Billing Panel --}}
        <div class="lg:w-80 xl:w-96 flex flex-col space-y-4">

            {{-- Customer Info (Collapsible on mobile) --}}
            <div class="card" x-data="{ expanded: false }">
                <div class="card-body">
                    <button @click="expanded = !expanded" type="button"
                            class="flex items-center justify-between w-full text-left">
                        <h3 class="text-sm font-semibold text-gray-900">Customer Details</h3>
                        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="expanded && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="expanded" x-collapse class="mt-3 space-y-3">
                        <input type="text" x-model="customerName" class="form-input" placeholder="Customer Name">
                        <input type="tel" x-model="customerPhone" class="form-input" placeholder="Phone Number">
                        <input type="text" x-model="doctorName" class="form-input" placeholder="Doctor Name (optional)">
                        <input type="text" x-model="patientName" class="form-input" placeholder="Patient Name (optional)">
                    </div>
                </div>
            </div>

            {{-- Totals --}}
            <div class="card">
                <div class="card-body space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="text-gray-900" x-text="formatCurrency(subtotal)">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">GST (incl.)</span>
                        <span class="text-gray-900" x-text="formatCurrency(totalGst)">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Item Discounts</span>
                        <span class="text-red-600" x-text="'- ' + formatCurrency(totalItemDiscount)">- ₹0.00</span>
                    </div>

                    {{-- Invoice Discount --}}
                    <div class="flex items-center gap-2 pt-1">
                        <span class="text-sm text-gray-500 flex-shrink-0">Inv. Disc</span>
                        <input type="number" x-model.number="invoiceDiscount" min="0" max="100" step="0.5"
                               class="w-16 h-7 text-center text-xs border border-gray-300 rounded"
                               placeholder="%">
                        <span class="text-xs text-gray-400">%</span>
                        <span class="text-sm text-red-600 ml-auto" x-text="'- ' + formatCurrency(invoiceDiscountTotal)">- ₹0.00</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Round Off</span>
                        <span class="text-gray-900" x-text="(roundOff >= 0 ? '+' : '') + formatNumber(roundOff)">0.00</span>
                    </div>

                    {{-- Grand Total --}}
                    <div class="border-t border-gray-200 pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-blue-600" x-text="formatCurrency(grandTotal)">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Mode --}}
            <div class="card">
                <div class="card-body space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900">Payment</h3>

                    <div class="grid grid-cols-2 gap-2">
                        <button @click="setPaymentMode('cash')" type="button"
                                :class="paymentMode === 'cash' ? 'bg-green-600 text-white border-green-600 ring-2 ring-green-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="py-2.5 px-3 rounded-lg border text-sm font-semibold transition-all">
                            Cash
                        </button>
                        <button @click="setPaymentMode('upi')" type="button"
                                :class="paymentMode === 'upi' ? 'bg-purple-600 text-white border-purple-600 ring-2 ring-purple-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="py-2.5 px-3 rounded-lg border text-sm font-semibold transition-all">
                            UPI
                        </button>
                        <button @click="setPaymentMode('credit')" type="button"
                                :class="paymentMode === 'credit' ? 'bg-amber-500 text-white border-amber-500 ring-2 ring-amber-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="py-2.5 px-3 rounded-lg border text-sm font-semibold transition-all">
                            Credit
                        </button>
                        <button @click="setPaymentMode('partial')" type="button"
                                :class="paymentMode === 'partial' ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="py-2.5 px-3 rounded-lg border text-sm font-semibold transition-all">
                            Partial
                        </button>
                    </div>

                    {{-- Paid Amount --}}
                    <div x-show="paymentMode !== 'credit'" x-transition>
                        <label class="text-xs text-gray-500 block mb-1">Paid Amount</label>
                        <input type="number"
                               x-model.number="paidAmount"
                               step="0.01"
                               min="0"
                               class="form-input text-lg font-bold text-right"
                               placeholder="0.00">
                    </div>

                    {{-- Balance / Change --}}
                    <template x-if="paymentMode === 'credit'">
                        <div class="p-3 bg-amber-50 rounded-lg text-center">
                            <p class="text-xs text-amber-600">Credit Amount</p>
                            <p class="text-lg font-bold text-amber-700" x-text="formatCurrency(grandTotal)">₹0.00</p>
                        </div>
                    </template>

                    <template x-if="paymentMode !== 'credit' && balanceAmount > 0">
                        <div class="p-3 bg-red-50 rounded-lg text-center">
                            <p class="text-xs text-red-600">Balance Due</p>
                            <p class="text-lg font-bold text-red-700" x-text="formatCurrency(balanceAmount)">₹0.00</p>
                        </div>
                    </template>

                    <template x-if="changeAmount > 0">
                        <div class="p-3 bg-green-50 rounded-lg text-center">
                            <p class="text-xs text-green-600">Change to Return</p>
                            <p class="text-lg font-bold text-green-700" x-text="formatCurrency(changeAmount)">₹0.00</p>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Complete Sale Button --}}
            <button @click="submitSale()"
                    :disabled="!canSubmit"
                    type="button"
                    class="w-full py-4 rounded-xl text-lg font-bold transition-all shadow-lg
                           disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none
                           bg-green-600 text-white hover:bg-green-700 active:scale-[0.98]
                           focus:outline-none focus:ring-4 focus:ring-green-200 safe-area-bottom">
                <template x-if="!submitting">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Complete Sale
                    </span>
                </template>
                <template x-if="submitting">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner text-white"></span>
                        Processing...
                    </span>
                </template>
            </button>

        </div>
    </div>

</div>
@endsection
