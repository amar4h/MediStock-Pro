{{--
    Stat Card Component
    Displays a metric card for the dashboard.

    @props:
        $title    - string : Card title (e.g., "Today's Sales")
        $value    - string : Primary display value (e.g., "₹12,450")
        $icon     - string : SVG path data for the icon
        $color    - string : Tailwind color name (blue, green, red, amber, purple, indigo)
        $subtitle - string : Optional subtitle text (e.g., "+12% from yesterday")

    Usage:
        @include('components.stat-card', [
            'title'    => "Today's Sales",
            'value'    => '₹12,450',
            'icon'     => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2...',
            'color'    => 'green',
            'subtitle' => '+12% from yesterday',
        ])
--}}

@php
    $color = $color ?? 'blue';

    $colorClasses = [
        'blue'   => ['border' => 'border-l-blue-500',   'bg' => 'bg-blue-100',   'text' => 'text-blue-600'],
        'green'  => ['border' => 'border-l-green-500',  'bg' => 'bg-green-100',  'text' => 'text-green-600'],
        'red'    => ['border' => 'border-l-red-500',    'bg' => 'bg-red-100',    'text' => 'text-red-600'],
        'amber'  => ['border' => 'border-l-amber-500',  'bg' => 'bg-amber-100',  'text' => 'text-amber-600'],
        'purple' => ['border' => 'border-l-purple-500', 'bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
        'indigo' => ['border' => 'border-l-indigo-500', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
    ];

    $classes = $colorClasses[$color] ?? $colorClasses['blue'];
@endphp

<div class="card border-l-4 {{ $classes['border'] }} hover:shadow-md transition-shadow">
    <div class="card-body">
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-500 truncate">{{ $title ?? 'Metric' }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 truncate">{{ $value ?? '0' }}</p>
                @if(!empty($subtitle))
                <p class="mt-1 text-xs text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>

            <div class="flex-shrink-0 ml-3">
                <div class="flex items-center justify-center w-11 h-11 {{ $classes['bg'] }} rounded-lg">
                    <svg class="w-6 h-6 {{ $classes['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if(!empty($icon))
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        @endif
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
