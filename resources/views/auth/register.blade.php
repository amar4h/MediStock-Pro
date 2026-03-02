@extends('layouts.guest')

@section('title', 'Register')
@section('heading', 'Create Your Account')
@section('subheading', 'Set up your pharmacy on MediStock Pro')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf

    {{-- Store Name --}}
    <div>
        <label for="store_name" class="form-label">Store / Pharmacy Name <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
            </div>
            <input type="text"
                   id="store_name"
                   name="store_name"
                   value="{{ old('store_name') }}"
                   class="form-input pl-10 @error('store_name') border-red-500 @enderror"
                   placeholder="e.g., MedPlus Pharmacy"
                   required>
        </div>
        @error('store_name')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Owner Name --}}
    <div>
        <label for="name" class="form-label">Owner Name <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   class="form-input pl-10 @error('name') border-red-500 @enderror"
                   placeholder="Your full name"
                   required>
        </div>
        @error('name')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="form-label">Email Address <span class="text-red-500">*</span></label>
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
                   autocomplete="email">
        </div>
        @error('email')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Phone --}}
    <div>
        <label for="phone" class="form-label">Phone Number <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <input type="tel"
                   id="phone"
                   name="phone"
                   value="{{ old('phone') }}"
                   class="form-input pl-10 @error('phone') border-red-500 @enderror"
                   placeholder="10-digit mobile number"
                   pattern="[0-9]{10}"
                   maxlength="10"
                   required>
        </div>
        @error('phone')
        <p class="form-error">{{ $message }}</p>
        @enderror
    </div>

    {{-- Password --}}
    <div>
        <label for="password" class="form-label">Password <span class="text-red-500">*</span></label>
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
                   placeholder="Minimum 8 characters"
                   required
                   minlength="8"
                   autocomplete="new-password">
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

    {{-- Confirm Password --}}
    <div>
        <label for="password_confirmation" class="form-label">Confirm Password <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <input type="password"
                   id="password_confirmation"
                   name="password_confirmation"
                   class="form-input pl-10"
                   placeholder="Re-enter your password"
                   required
                   autocomplete="new-password">
        </div>
    </div>

    {{-- Terms --}}
    <div class="flex items-start gap-2">
        <input type="checkbox"
               name="terms"
               id="terms"
               class="w-4 h-4 mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
               required>
        <label for="terms" class="text-sm text-gray-600">
            I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and
            <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
        </label>
    </div>

    {{-- Submit --}}
    <button type="submit"
            class="btn-primary w-full py-3 text-base"
            x-data="{ loading: false }"
            @click="loading = true"
            :disabled="loading">
        <template x-if="!loading">
            <span>Create Account</span>
        </template>
        <template x-if="loading">
            <span class="flex items-center gap-2">
                <span class="spinner spinner-sm"></span>
                Creating account...
            </span>
        </template>
    </button>

</form>
@endsection

@section('footer')
<p class="text-sm text-gray-600">
    Already have an account?
    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-semibold">
        Sign in
    </a>
</p>
@endsection
