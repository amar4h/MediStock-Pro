<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e40af">

    <title>@yield('title', 'Welcome') - MediStock Pro</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-blue-50">

    <div class="min-h-full flex flex-col items-center justify-center px-4 py-8 sm:py-12">

        {{-- Logo & Branding --}}
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center gap-3 mb-3">
                <div class="flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl shadow-lg shadow-blue-200">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">MediStock Pro</h1>
            </div>
            <p class="text-sm text-gray-500">Medical Store Management System</p>
        </div>

        {{-- Card Container --}}
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 p-6 sm:p-8">

                {{-- Page Title --}}
                @hasSection('heading')
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-semibold text-gray-900">@yield('heading')</h2>
                    @hasSection('subheading')
                    <p class="mt-1 text-sm text-gray-500">@yield('subheading')</p>
                    @endif
                </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="mb-4">
                    @include('components.alert', ['type' => 'success', 'message' => session('success')])
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4">
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

                {{-- Content --}}
                @yield('content')

            </div>

            {{-- Below Card Links --}}
            @hasSection('footer')
            <div class="mt-6 text-center">
                @yield('footer')
            </div>
            @endif
        </div>

        {{-- App Footer --}}
        <div class="mt-12 text-center">
            <p class="text-xs text-gray-400">MediStock Pro v1.0</p>
            <p class="text-xs text-gray-400 mt-1">Pharmacy Management for Indian Medical Stores</p>
        </div>
    </div>

</body>
</html>
