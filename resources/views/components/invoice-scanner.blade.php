{{--
    Invoice Scanner Component
    Used on the Purchase Create page to scan and OCR purchase invoices.

    Usage: @include('components.invoice-scanner')
    Requires: x-data="invoiceScanner()" on a parent or this component wraps itself.
--}}

<div x-data="invoiceScanner()" class="mb-6">

    {{-- Scanner Card --}}
    <div class="card">
        <div class="card-body">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Invoice Scanner</h3>
                        <p class="text-xs text-gray-500">Scan a purchase invoice to auto-fill the form</p>
                    </div>
                </div>

                {{-- Confidence Legend --}}
                <div class="hidden sm:flex items-center gap-3 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-high"></span> High
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-medium"></span> Medium
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-low"></span> Low
                    </span>
                </div>
            </div>

            {{-- STATE: Initial - Show scan button --}}
            <div x-show="!scanning && !scanned && !errorMessage">
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    {{-- Camera capture (mobile) --}}
                    <label class="btn-primary cursor-pointer w-full sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Scan Invoice</span>
                        <input type="file"
                               x-ref="fileInput"
                               accept="image/*"
                               capture="environment"
                               @change="handleImageSelect($event)"
                               class="hidden">
                    </label>

                    {{-- File upload (desktop) --}}
                    <label class="btn-secondary cursor-pointer w-full sm:w-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span>Upload Image</span>
                        <input type="file"
                               accept="image/*"
                               @change="handleImageSelect($event)"
                               class="hidden">
                    </label>

                    <span class="text-xs text-gray-400">JPG, PNG up to 15MB</span>
                </div>
            </div>

            {{-- STATE: Scanning - Show progress --}}
            <div x-show="scanning" x-cloak class="py-4">
                <div class="flex flex-col items-center gap-4">
                    {{-- Preview Image --}}
                    <div x-show="previewUrl" class="relative w-full max-w-xs mx-auto">
                        <img :src="previewUrl"
                             alt="Invoice preview"
                             class="w-full rounded-lg shadow-sm border border-gray-200 opacity-60">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="spinner-xl text-blue-600"></div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="w-full max-w-xs">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">Processing invoice...</span>
                            <span class="text-sm text-gray-500" x-text="progress + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                 :style="'width: ' + progress + '%'"></div>
                        </div>
                    </div>

                    <button @click="cancelScan()" class="btn-ghost btn-sm text-gray-500">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- STATE: Scanned Successfully --}}
            <div x-show="scanned" x-cloak>
                {{-- Success Banner --}}
                <div class="rounded-lg p-3 mb-3"
                     :class="{
                         'bg-green-50 border border-green-200': overallConfidence >= 0.8,
                         'bg-amber-50 border border-amber-200': overallConfidence >= 0.5 && overallConfidence < 0.8,
                         'bg-red-50 border border-red-200': overallConfidence < 0.5
                     }">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <template x-if="overallConfidence >= 0.8">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <template x-if="overallConfidence >= 0.5 && overallConfidence < 0.8">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </template>
                            <template x-if="overallConfidence < 0.5">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                        </div>

                        <div class="flex-1">
                            <p class="text-sm font-medium"
                               :class="{
                                   'text-green-800': overallConfidence >= 0.8,
                                   'text-amber-800': overallConfidence >= 0.5 && overallConfidence < 0.8,
                                   'text-red-800': overallConfidence < 0.5
                               }">
                                <span x-show="overallConfidence >= 0.8">Invoice scanned successfully!</span>
                                <span x-show="overallConfidence >= 0.5 && overallConfidence < 0.8">Partial scan - some fields may need correction</span>
                                <span x-show="overallConfidence < 0.5">Low confidence scan - please verify all fields</span>
                            </p>
                            <p class="text-xs mt-1 opacity-75"
                               :class="{
                                   'text-green-700': overallConfidence >= 0.8,
                                   'text-amber-700': overallConfidence >= 0.5 && overallConfidence < 0.8,
                                   'text-red-700': overallConfidence < 0.5
                               }">
                                Confidence: <span x-text="Math.round((scanResult?.overall_confidence || 0) * 100)"></span>%
                                &mdash; Fields highlighted by confidence level. Please review before saving.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Warnings --}}
                <template x-if="warnings.length > 0">
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 mb-3">
                        <p class="text-xs font-semibold text-amber-800 mb-1">Warnings:</p>
                        <ul class="text-xs text-amber-700 space-y-0.5">
                            <template x-for="(warning, i) in warnings" :key="i">
                                <li class="flex items-start gap-1">
                                    <span class="mt-1 flex-shrink-0 w-1 h-1 rounded-full bg-amber-500"></span>
                                    <span x-text="warning"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <button @click="retake()" class="btn-secondary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Re-scan
                    </button>

                    <button @click="viewOriginal()" class="btn-ghost btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span x-text="showOriginal ? 'Hide Original' : 'View Original'"></span>
                    </button>

                    <button @click="cancelScan()" class="btn-ghost btn-sm text-red-500">
                        Clear Scan
                    </button>
                </div>

                {{-- Original Image Viewer --}}
                <div x-show="showOriginal" x-cloak x-transition class="mt-3">
                    <img :src="originalUrl"
                         alt="Original invoice"
                         class="w-full max-w-md rounded-lg shadow-sm border border-gray-200">
                </div>

                {{-- Mobile Confidence Legend --}}
                <div class="sm:hidden mt-3 flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-high"></span> High
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-medium"></span> Medium
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="confidence-dot-low"></span> Low
                    </span>
                </div>
            </div>

            {{-- STATE: Error --}}
            <div x-show="!scanning && !scanned && errorMessage" x-cloak>
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 mb-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-red-800" x-text="errorMessage"></p>
                            <p class="text-xs text-red-600 mt-1">You can try again or enter the details manually below.</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="retake()" class="btn-primary btn-sm">
                        Try Again
                    </button>
                    <button @click="cancelScan()" class="btn-ghost btn-sm">
                        Enter Manually
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
