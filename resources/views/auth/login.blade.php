@extends('layouts.guest')

@section('title', 'Login')
@section('heading', 'Welcome Back')
@section('subheading', 'Sign in to your MediStock Pro account')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    {{-- Email --}}
    <div>
        <label for="email" class="form-label">Email Address</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
            </div>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="form-input pl-10 @error('email') border-red-500 @enderror"
                   placeholder="you@example.com"
                   required
                   autofocus
                   autocomplete="email">
        </div>
        @error('email')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Password --}}
    <div>
        <label for="password" class="form-label">Password</label>
        <div class="relative" x-data="{ showPassword: false }">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <input :type="showPassword ? 'text' : 'password'"
                   id="password"
                   name="password"
                   class="form-input pl-10 pr-10 @error('password') border-red-500 @enderror"
                   placeholder="Enter your password"
                   required
                   autocomplete="current-password">
            <button type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
            </button>
        </div>
        @error('password')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Remember Me --}}
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox"
                   name="remember"
                   {{ old('remember') ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-600">Remember me</span>
        </label>

        @if(Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            Forgot password?
        </a>
        @endif
    </div>

    {{-- Submit --}}
    <button type="submit"
            class="btn-primary w-full py-3 text-base"
            x-data="{ loading: false }"
            @click="loading = true"
            :disabled="loading">
        <template x-if="!loading">
            <span>Sign In</span>
        </template>
        <template x-if="loading">
            <span class="flex items-center gap-2">
                <span class="spinner spinner-sm"></span>
                Signing in...
            </span>
        </template>
    </button>

</form>
@endsection

@section('footer')
<p class="text-sm text-gray-600">
    Don't have an account?
    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
        Register your store
    </a>
</p>
@endsection
