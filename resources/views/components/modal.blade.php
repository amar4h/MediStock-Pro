{{--
    Modal Component (Alpine.js)
    Displays a modal dialog with backdrop overlay and slide-up animation on mobile.

    @props:
        $name     - string : Unique modal name (used for Alpine x-data reference)
        $maxWidth - string : Max width class (sm, md, lg, xl, 2xl). Default: lg
        $title    - string : Optional modal title

    Usage:
        @include('components.modal', [
            'name' => 'confirm-delete',
            'maxWidth' => 'md',
            'title' => 'Confirm Deletion',
        ])

        Control via Alpine:
            <button @click="$dispatch('open-modal', 'confirm-delete')">Open</button>

        Or in the parent x-data:
            showModal = false; // toggle directly
--}}

@php
    $name     = $name ?? 'modal';
    $title    = $title ?? '';
    $maxWidth = $maxWidth ?? 'lg';

    $maxWidthClasses = [
        'sm'  => 'sm:max-w-sm',
        'md'  => 'sm:max-w-md',
        'lg'  => 'sm:max-w-lg',
        'xl'  => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ];

    $widthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['lg'];
@endphp

<div x-data="{ show: false, name: '{{ $name }}' }"
     x-init="
        $watch('show', value => {
            document.body.style.overflow = value ? 'hidden' : '';
        });
     "
     @open-modal.window="if ($event.detail === name) show = true"
     @close-modal.window="if ($event.detail === name) show = false"
     @keydown.escape.window="show = false"
     x-show="show"
     x-cloak
     class="fixed inset-0 z-50"
     role="dialog"
     aria-modal="true"
     :aria-labelledby="'modal-title-' + name">

    {{-- Backdrop --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="show = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm">
    </div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
            <div x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                 @click.outside="show = false"
                 class="relative w-full {{ $widthClass }} bg-white rounded-t-2xl sm:rounded-2xl shadow-xl
                        max-h-[90vh] overflow-hidden flex flex-col">

                {{-- Drag Handle (Mobile) --}}
                <div class="sm:hidden flex justify-center pt-3 pb-1">
                    <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
                </div>

                {{-- Modal Header --}}
                @if($title)
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" :id="'modal-title-' + name">
                        {{ $title }}
                    </h3>
                    <button @click="show = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @else
                {{-- Close button without title --}}
                <div class="absolute top-3 right-3 z-10">
                    <button @click="show = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                            aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endif

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 custom-scrollbar">
                    {{ $slot ?? '' }}
                </div>

                {{-- Modal Footer (optional, yielded by parent) --}}
                @if(isset($footer))
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50 safe-area-bottom">
                    {{ $footer }}
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
