@extends('layouts.app')

@section('title', 'Reports')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Reports</span>
@endsection

@section('content')
<div x-data="{
    selectedReport: '{{ request('report', '') }}',
    dateFrom: '{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}',
    dateTo: '{{ request('date_to', now()->format('Y-m-d')) }}',
    period: '{{ request('period', 'daily') }}',
    loading: false
}" class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900">Reports</h1>
        <p class="text-sm text-gray-500">Generate and view business analytics and financial reports</p>
    </div>

    {{-- Report Type Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- Sales Report --}}
        <button @click="selectedReport = 'sales'"
                :class="selectedReport === 'sales' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Sales Report</h3>
                        <p class="text-xs text-gray-500">Daily/monthly sales breakdown</p>
                    </div>
                </div>
            </div>
        </button>

        {{-- Profit Report --}}
        <button @click="selectedReport = 'profit'"
                :class="selectedReport === 'profit' ? 'ring-2 ring-green-500 bg-green-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Gross Profit</h3>
                        <p class="text-xs text-gray-500">Sales revenue minus cost</p>
                    </div>
                </div>
            </div>
        </button>

        {{-- Net Profit Report --}}
        <button @click="selectedReport = 'net-profit'"
                :class="selectedReport === 'net-profit' ? 'ring-2 ring-indigo-500 bg-indigo-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-indigo-100 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Net Profit</h3>
                        <p class="text-xs text-gray-500">Profit after expenses</p>
                    </div>
                </div>
            </div>
        </button>

        {{-- Expense Report --}}
        <button @click="selectedReport = 'expenses'"
                :class="selectedReport === 'expenses' ? 'ring-2 ring-red-500 bg-red-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Expenses</h3>
                        <p class="text-xs text-gray-500">Category-wise expense summary</p>
                    </div>
                </div>
            </div>
        </button>

        {{-- GST Summary --}}
        <button @click="selectedReport = 'gst'"
                :class="selectedReport === 'gst' ? 'ring-2 ring-purple-500 bg-purple-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">GST Summary</h3>
                        <p class="text-xs text-gray-500">CGST, SGST breakdown</p>
                    </div>
                </div>
            </div>
        </button>

        {{-- Item-wise Profit --}}
        <button @click="selectedReport = 'item-profit'"
                :class="selectedReport === 'item-profit' ? 'ring-2 ring-amber-500 bg-amber-50' : 'bg-white hover:shadow-md'"
                class="card text-left transition-all cursor-pointer">
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 bg-amber-100 rounded-lg">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Item-wise Profit</h3>
                        <p class="text-xs text-gray-500">Profitability per product</p>
                    </div>
                </div>
            </div>
        </button>
    </div>

    {{-- Filters (show when a report is selected) --}}
    <div x-show="selectedReport" x-transition x-cloak>
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col sm:flex-row gap-3 items-end">
                    <input type="hidden" name="report" :value="selectedReport">

                    <div class="flex-1">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" x-model="dateFrom" class="form-input">
                    </div>
                    <div class="flex-1">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" x-model="dateTo" class="form-input">
                    </div>
                    <div class="flex-1">
                        <label class="form-label">Period</label>
                        <select name="period" x-model="period" class="form-select">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Report Display Area --}}
    @if(request('report'))
    <div class="card">
        <div class="card-body">
            {{-- Report Header --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">
                        @switch(request('report'))
                            @case('sales') Sales Report @break
                            @case('profit') Gross Profit Report @break
                            @case('net-profit') Net Profit Report @break
                            @case('expenses') Expense Report @break
                            @case('gst') GST Summary @break
                            @case('item-profit') Item-wise Profit @break
                            @default Report
                        @endswitch
                    </h2>
                    <p class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse(request('date_from'))->format('d M Y') }}
                        to {{ \Carbon\Carbon::parse(request('date_to'))->format('d M Y') }}
                    </p>
                </div>
                <button onclick="window.print()" class="btn-secondary btn-sm no-print">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
            </div>

            {{-- Summary Row --}}
            @if(isset($reportSummary))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach($reportSummary as $key => $value)
                <div class="bg-gray-50 rounded-lg px-4 py-3">
                    <p class="text-xs text-gray-500">{{ $key }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ $value }}</p>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Report Table --}}
            @if(isset($reportData) && count($reportData) > 0)
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach(($reportColumns ?? []) as $col)
                            <th class="{{ $col['class'] ?? '' }}">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $row)
                        <tr>
                            @foreach(($reportColumns ?? []) as $col)
                            <td class="{{ $col['class'] ?? '' }}">
                                {{ $row->{$col['key']} ?? ($row[$col['key']] ?? '-') }}
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="text-sm">No data available for the selected period.</p>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
