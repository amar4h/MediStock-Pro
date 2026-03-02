{{--
    Data Table Component
    Reusable responsive table with sortable columns, pagination, and empty state.

    @props:
        $headers     - array  : Column definitions [['label' => 'Name', 'key' => 'name', 'sortable' => true], ...]
        $rows        - mixed  : Eloquent paginator or array of rows
        $emptyTitle  - string : Title when no rows (default: "No data found")
        $emptyText   - string : Description when no rows
        $sortBy      - string : Current sort column key
        $sortDir     - string : Current sort direction (asc/desc)
        $id          - string : Table ID for targeting

    Usage:
        @include('components.data-table', [
            'headers' => [
                ['label' => 'Name', 'key' => 'name', 'sortable' => true],
                ['label' => 'Actions', 'key' => 'actions'],
            ],
            'rows' => $items,
            'emptyTitle' => 'No items found',
            'emptyText' => 'Create your first item to get started.',
        ])

    Slots: The calling view must provide the <tbody> rows separately.
--}}

@php
    $headers    = $headers ?? [];
    $rows       = $rows ?? [];
    $emptyTitle = $emptyTitle ?? 'No data found';
    $emptyText  = $emptyText ?? 'Try adjusting your search or filter criteria.';
    $sortBy     = $sortBy ?? request('sort_by', '');
    $sortDir    = $sortDir ?? request('sort_dir', 'asc');
    $tableId    = $id ?? 'data-table';

    $hasRows = is_object($rows) ? $rows->count() > 0 : count($rows) > 0;
@endphp

<div class="card" id="{{ $tableId }}">

    {{-- Table Wrapper with horizontal scroll on mobile --}}
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($headers as $header)
                    <th class="{{ $header['class'] ?? '' }}">
                        @if(!empty($header['sortable']))
                        <a href="{{ request()->fullUrlWithQuery([
                                'sort_by' => $header['key'],
                                'sort_dir' => ($sortBy === $header['key'] && $sortDir === 'asc') ? 'desc' : 'asc'
                            ]) }}"
                           class="inline-flex items-center gap-1 hover:text-gray-700 transition-colors">
                            {{ $header['label'] }}
                            <span class="inline-flex flex-col">
                                @if($sortBy === $header['key'] && $sortDir === 'asc')
                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5.293 9.707l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 7.414l-3.293 3.293a1 1 0 01-1.414-1.414z"/>
                                </svg>
                                @elseif($sortBy === $header['key'] && $sortDir === 'desc')
                                <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M14.707 10.293l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 12.586l3.293-3.293a1 1 0 111.414 1.414z"/>
                                </svg>
                                @else
                                <svg class="w-3 h-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 3l4 4H6l4-4zm0 14l-4-4h8l-4 4z"/>
                                </svg>
                                @endif
                            </span>
                        </a>
                        @else
                        {{ $header['label'] }}
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if($hasRows)
                    {{ $slot ?? '' }}
                @endif
            </tbody>
        </table>

        {{-- Empty State --}}
        @if(!$hasRows)
        <div class="py-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h3 class="mt-3 text-sm font-medium text-gray-900">{{ $emptyTitle }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $emptyText }}</p>
        </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if(is_object($rows) && method_exists($rows, 'links'))
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 sm:flex sm:items-center sm:justify-between">
        {{-- Info --}}
        <div class="text-sm text-gray-500 mb-2 sm:mb-0">
            Showing
            <span class="font-medium">{{ $rows->firstItem() ?? 0 }}</span>
            to
            <span class="font-medium">{{ $rows->lastItem() ?? 0 }}</span>
            of
            <span class="font-medium">{{ $rows->total() }}</span>
            results
        </div>

        {{-- Page Links --}}
        <div class="flex items-center gap-1">
            {{-- Previous --}}
            @if($rows->onFirstPage())
            <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                Previous
            </span>
            @else
            <a href="{{ $rows->previousPageUrl() }}"
               class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Previous
            </a>
            @endif

            {{-- Page Numbers --}}
            @foreach($rows->getUrlRange(max(1, $rows->currentPage() - 2), min($rows->lastPage(), $rows->currentPage() + 2)) as $page => $url)
            @if($page == $rows->currentPage())
            <span class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg">
                {{ $page }}
            </span>
            @else
            <a href="{{ $url }}"
               class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors hidden sm:inline-block">
                {{ $page }}
            </a>
            @endif
            @endforeach

            {{-- Next --}}
            @if($rows->hasMorePages())
            <a href="{{ $rows->nextPageUrl() }}"
               class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Next
            </a>
            @else
            <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                Next
            </span>
            @endif
        </div>
    </div>
    @endif

</div>
