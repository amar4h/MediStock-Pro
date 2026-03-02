{{--
    Alert / Notification Component
    Displays an alert banner with icon, colored styling, and optional dismiss.

    @props:
        $type    - string : success | error | warning | info
        $message - string : The alert message text
        $list    - array  : Optional list of items to display below the message
        $dismiss - bool   : Whether the alert is dismissible (default: true)

    Usage:
        @include('components.alert', [
            'type'    => 'success',
            'message' => 'Item saved successfully!',
        ])

        @include('components.alert', [
            'type'    => 'error',
            'message' => 'Please fix the following errors:',
            'list'    => $errors->all(),
        ])
--}}

@php
    $type    = $type ?? 'info';
    $message = $message ?? '';
    $list    = $list ?? [];
    $dismiss = $dismiss ?? true;

    $styles = [
        'success' => [
            'bg'    => 'bg-green-50 border-green-300',
            'text'  => 'text-green-800',
            'icon'  => 'text-green-500',
            'hover' => 'hover:bg-green-100',
        ],
        'error' => [
            'bg'    => 'bg-red-50 border-red-300',
            'text'  => 'text-red-800',
            'icon'  => 'text-red-500',
            'hover' => 'hover:bg-red-100',
        ],
        'warning' => [
            'bg'    => 'bg-amber-50 border-amber-300',
            'text'  => 'text-amber-800',
            'icon'  => 'text-amber-500',
            'hover' => 'hover:bg-amber-100',
        ],
        'info' => [
            'bg'    => 'bg-blue-50 border-blue-300',
            'text'  => 'text-blue-800',
            'icon'  => 'text-blue-500',
            'hover' => 'hover:bg-blue-100',
        ],
    ];

    $s = $styles[$type] ?? $styles['info'];
@endphp

<div x-data="{ visible: true }"
     x-show="visible"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="rounded-lg border px-4 py-3 {{ $s['bg'] }}"
     role="alert">
    <div class="flex items-start gap-3">

        {{-- Icon --}}
        <div class="flex-shrink-0 mt-0.5">
            @switch($type)
                @case('success')
                <svg class="w-5 h-5 {{ $s['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @break

                @case('error')
                <svg class="w-5 h-5 {{ $s['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @break

                @case('warning')
                <svg class="w-5 h-5 {{ $s['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                @break

                @default
                <svg class="w-5 h-5 {{ $s['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endswitch
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium {{ $s['text'] }}">{{ $message }}</p>

            @if(!empty($list))
            <ul class="mt-2 text-sm {{ $s['text'] }} opacity-90 list-disc list-inside space-y-0.5">
                @foreach($list as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Dismiss Button --}}
        @if($dismiss)
        <button @click="visible = false"
                class="flex-shrink-0 p-1 rounded-lg {{ $s['text'] }} {{ $s['hover'] }} transition-colors opacity-70 hover:opacity-100"
                aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        @endif

    </div>
</div>
