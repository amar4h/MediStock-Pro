@extends('layouts.app')

@section('title', isset($item) ? 'Edit Item' : 'Add Item')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<a href="{{ route('items.index') }}" class="hover:text-blue-600">Items</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">{{ isset($item) ? 'Edit' : 'Add' }}</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">{{ isset($item) ? 'Edit Item' : 'Add New Item' }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ isset($item) ? 'Update item details' : 'Add a new medicine or product to your catalog' }}</p>
    </div>

    {{-- Form --}}
    <form method="POST"
          action="{{ isset($item) ? route('items.update', $item->id) : route('items.store') }}"
          class="space-y-6">
        @csrf
        @if(isset($item))
        @method('PUT')
        @endif

        {{-- Basic Information --}}
        <div class="card">
            <div class="card-body space-y-4">
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Basic Information</h2>

                {{-- Name --}}
                <div>
                    <label for="name" class="form-label">Item Name <span class="text-red-500">*</span></label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $item->name ?? '') }}"
                           class="form-input @error('name') border-red-500 @enderror"
                           placeholder="e.g., Paracetamol 500mg Tab"
                           required>
                    @error('name')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Composition --}}
                <div>
                    <label for="composition" class="form-label">Composition / Salt</label>
                    <input type="text"
                           id="composition"
                           name="composition"
                           value="{{ old('composition', $item->composition ?? '') }}"
                           class="form-input @error('composition') border-red-500 @enderror"
                           placeholder="e.g., Paracetamol 500mg">
                    @error('composition')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category + Manufacturer Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="form-label">Category <span class="text-red-500">*</span></label>
                        <select id="category" name="category" class="form-select @error('category') border-red-500 @enderror" required>
                            <option value="">Select Category</option>
                            @foreach(($categories ?? ['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream/Ointment', 'Drops', 'Powder', 'Inhaler', 'Surgical', 'OTC', 'Ayurvedic', 'Other']) as $cat)
                            <option value="{{ $cat }}" {{ old('category', $item->category->name ?? '') === $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="manufacturer" class="form-label">Manufacturer</label>
                        <input type="text"
                               id="manufacturer"
                               name="manufacturer"
                               value="{{ old('manufacturer', $item->manufacturer->name ?? '') }}"
                               class="form-input @error('manufacturer') border-red-500 @enderror"
                               placeholder="e.g., Cipla Ltd"
                               list="manufacturers-list">
                        <datalist id="manufacturers-list">
                            @foreach(($manufacturers ?? []) as $mfg)
                            <option value="{{ $mfg }}">
                            @endforeach
                        </datalist>
                        @error('manufacturer')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Tax & Pricing --}}
        <div class="card">
            <div class="card-body space-y-4">
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Tax & Pricing</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- HSN Code --}}
                    <div>
                        <label for="hsn_code" class="form-label">HSN Code</label>
                        <input type="text"
                               id="hsn_code"
                               name="hsn_code"
                               value="{{ old('hsn_code', $item->hsn_code ?? '') }}"
                               class="form-input @error('hsn_code') border-red-500 @enderror"
                               placeholder="e.g., 30049099">
                        @error('hsn_code')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- GST % --}}
                    <div>
                        <label for="gst_percent" class="form-label">GST % <span class="text-red-500">*</span></label>
                        <select id="gst_percent" name="gst_percent" class="form-select @error('gst_percent') border-red-500 @enderror" required>
                            <option value="">Select GST%</option>
                            @foreach([0, 5, 12, 18, 28] as $gst)
                            <option value="{{ $gst }}" {{ old('gst_percent', $item->gst_percent ?? 12) == $gst ? 'selected' : '' }}>
                                {{ $gst }}%
                            </option>
                            @endforeach
                        </select>
                        @error('gst_percent')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Margin % --}}
                    <div>
                        <label for="margin_percent" class="form-label">Margin %</label>
                        <input type="number"
                               id="margin_percent"
                               name="margin_percent"
                               value="{{ old('margin_percent', $item->margin_percent ?? '') }}"
                               class="form-input @error('margin_percent') border-red-500 @enderror"
                               step="0.01"
                               min="0"
                               max="100"
                               placeholder="e.g., 20">
                        @error('margin_percent')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Details --}}
        <div class="card">
            <div class="card-body space-y-4">
                <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Additional Details</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Barcode --}}
                    <div>
                        <label for="barcode" class="form-label">Barcode</label>
                        <input type="text"
                               id="barcode"
                               name="barcode"
                               value="{{ old('barcode', $item->barcode ?? '') }}"
                               class="form-input @error('barcode') border-red-500 @enderror"
                               placeholder="Scan or enter barcode">
                        @error('barcode')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Unit --}}
                    <div>
                        <label for="unit" class="form-label">Unit</label>
                        <select id="unit" name="unit" class="form-select @error('unit') border-red-500 @enderror">
                            <option value="">Select Unit</option>
                            @foreach(['Tab', 'Cap', 'Bottle', 'Strip', 'Box', 'Vial', 'Amp', 'Tube', 'Sachet', 'Piece', 'ML', 'GM', 'KG', 'Litre'] as $unit)
                            <option value="{{ $unit }}" {{ old('unit', $item->unit ?? '') === $unit ? 'selected' : '' }}>
                                {{ $unit }}
                            </option>
                            @endforeach
                        </select>
                        @error('unit')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Schedule --}}
                    <div>
                        <label for="schedule" class="form-label">Schedule</label>
                        <select id="schedule" name="schedule" class="form-select @error('schedule') border-red-500 @enderror">
                            <option value="">None</option>
                            @foreach(['H', 'H1', 'G', 'X', 'Schedule C', 'Schedule C1'] as $sch)
                            <option value="{{ $sch }}" {{ old('schedule', $item->schedule ?? '') === $sch ? 'selected' : '' }}>
                                {{ $sch }}
                            </option>
                            @endforeach
                        </select>
                        @error('schedule')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="form-label">Status</label>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="active"
                                   {{ old('status', $item->status ?? 'active') === 'active' ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="inactive"
                                   {{ old('status', $item->status ?? 'active') === 'inactive' ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Inactive</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
            <a href="{{ route('items.index') }}" class="btn-secondary w-full sm:w-auto">
                Cancel
            </a>
            <button type="submit" class="btn-primary w-full sm:w-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7"/>
                </svg>
                {{ isset($item) ? 'Update Item' : 'Save Item' }}
            </button>
        </div>
    </form>

</div>
@endsection
