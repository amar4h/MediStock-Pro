<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <title>@yield('title', 'Dashboard') - MediStock Pro</title>

    {{-- Preload fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    {{-- Vite CSS/JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="h-full bg-gray-50" x-data x-cloak>

    <div class="flex h-full">

        {{-- ============================================================
             SIDEBAR NAVIGATION
             ============================================================ --}}

        {{-- Mobile backdrop --}}
        <div x-show="$store.sidebar.open"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.sidebar.close()"
             class="fixed inset-0 z-40 bg-black/50 lg:hidden">
        </div>

        {{-- Sidebar --}}
        <aside :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 shadow-lg
                      transform transition-transform duration-300 ease-in-out
                      lg:translate-x-0 lg:static lg:shadow-none custom-scrollbar overflow-y-auto">

            {{-- Logo --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-200">
                <div class="flex items-center justify-center w-9 h-9 bg-blue-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">MediStock Pro</h1>
                    <p class="text-xs text-gray-500">Pharmacy Management</p>
                </div>
            </div>

            {{-- Store Info --}}
            @auth
            <div class="px-5 py-3 bg-blue-50 border-b border-blue-100">
                <p class="text-xs font-medium text-blue-800 truncate">{{ auth()->user()->tenant->name ?? 'My Store' }}</p>
                <p class="text-xs text-blue-600 truncate">{{ auth()->user()->name ?? 'User' }}</p>
            </div>
            @endauth

            {{-- Navigation Links --}}
            <nav class="px-3 py-4 space-y-1">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                {{-- Items --}}
                <a href="{{ route('items.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('items.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Items</span>
                </a>

                {{-- Purchases --}}
                <a href="{{ route('purchases.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('purchases.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                    <span>Purchases</span>
                </a>

                {{-- Sales --}}
                <a href="{{ route('sales.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('sales.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span>Sales</span>
                </a>

                {{-- Inventory --}}
                <a href="{{ route('inventory.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('inventory.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    <span>Inventory</span>
                </a>

                {{-- Customers --}}
                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('customers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Customers</span>
                </a>

                {{-- Suppliers --}}
                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('suppliers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                    <span>Suppliers</span>
                </a>

                {{-- Expenses --}}
                <a href="{{ route('expenses.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('expenses.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Expenses</span>
                </a>

                {{-- Reports --}}
                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Reports</span>
                </a>

                {{-- Divider --}}
                <div class="my-3 border-t border-gray-200"></div>

                {{-- Settings --}}
                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                          {{ request()->routeIs('settings.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Settings</span>
                </a>

            </nav>

            {{-- Sidebar Footer --}}
            <div class="mt-auto px-5 py-4 border-t border-gray-200">
                <p class="text-xs text-gray-400 text-center">MediStock Pro v1.0</p>
            </div>
        </aside>

        {{-- ============================================================
             MAIN CONTENT AREA
             ============================================================ --}}
        <div class="flex-1 flex flex-col min-h-0 min-w-0">

            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
                <div class="flex items-center justify-between px-4 py-3 lg:px-6">

                    {{-- Left: Hamburger + Page Title --}}
                    <div class="flex items-center gap-3">
                        {{-- Mobile hamburger --}}
                        <button @click="$store.sidebar.toggle()"
                                class="lg:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:bg-gray-100 touch-target"
                                aria-label="Toggle navigation">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <h2 class="text-lg font-semibold text-gray-900 truncate">
                            @yield('title', 'Dashboard')
                        </h2>
                    </div>

                    {{-- Right: User Info + Logout --}}
                    <div class="flex items-center gap-3">
                        @auth
                        {{-- User info (hidden on small mobile) --}}
                        <div class="hidden sm:flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                                <span class="text-sm font-semibold text-blue-700">
                                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                </span>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-medium text-gray-700 leading-tight">{{ auth()->user()->name ?? 'User' }}</p>
                                <p class="text-xs text-gray-500 leading-tight">{{ auth()->user()->tenant->name ?? '' }}</p>
                            </div>
                        </div>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors touch-target"
                                    title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                        @endauth
                    </div>
                </div>

                {{-- Breadcrumbs (optional) --}}
                @hasSection('breadcrumbs')
                <div class="px-4 pb-2 lg:px-6">
                    <nav class="flex text-sm text-gray-500" aria-label="Breadcrumb">
                        @yield('breadcrumbs')
                    </nav>
                </div>
                @endif
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6 custom-scrollbar">
                {{-- Flash Messages --}}
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition class="mb-4">
                    @include('components.alert', ['type' => 'success', 'message' => session('success')])
                </div>
                @endif

                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition class="mb-4">
                    @include('components.alert', ['type' => 'error', 'message' => session('error')])
                </div>
                @endif

                @if($errors->any())
                <div class="mb-4">
                    @include('components.alert', [
                        'type' => 'error',
                        'message' => 'Please fix the following errors:',
                        'list' => $errors->all()
                    ])
                </div>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="hidden lg:block border-t border-gray-200 bg-white px-6 py-3 no-print">
                <p class="text-xs text-gray-400 text-center">MediStock Pro v1.0</p>
            </footer>
        </div>
    </div>

    {{-- ============================================================
         TOAST NOTIFICATION CONTAINER
         ============================================================ --}}
    <div class="fixed top-4 right-4 z-[60] space-y-2 w-80 max-w-[calc(100vw-2rem)] no-print"
         aria-live="polite">
        <template x-for="notification in $store.notification.items" :key="notification.id">
            <div x-show="notification.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-1 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-1 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 :class="{
                     'bg-green-50 border-green-400 text-green-800': notification.type === 'success',
                     'bg-red-50 border-red-400 text-red-800': notification.type === 'error',
                     'bg-amber-50 border-amber-400 text-amber-800': notification.type === 'warning',
                     'bg-blue-50 border-blue-400 text-blue-800': notification.type === 'info'
                 }"
                 class="flex items-start gap-3 px-4 py-3 rounded-lg border shadow-lg animate-fade-in-up">

                {{-- Icon --}}
                <div class="flex-shrink-0 mt-0.5">
                    {{-- Success --}}
                    <template x-if="notification.type === 'success'">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    {{-- Error --}}
                    <template x-if="notification.type === 'error'">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    {{-- Warning --}}
                    <template x-if="notification.type === 'warning'">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    {{-- Info --}}
                    <template x-if="notification.type === 'info'">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>

                {{-- Message --}}
                <p class="text-sm font-medium flex-1" x-text="notification.message"></p>

                {{-- Dismiss --}}
                <button @click="$store.notification.dismiss(notification.id)"
                        class="flex-shrink-0 p-0.5 rounded hover:bg-black/5 transition-colors">
                    <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    @stack('scripts')
</body>
</html>
