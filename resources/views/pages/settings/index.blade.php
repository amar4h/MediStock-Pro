@extends('layouts.app')

@section('title', 'Settings')

@section('breadcrumbs')
<a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
<span class="mx-2">/</span>
<span class="text-gray-900 font-medium">Settings</span>
@endsection

@section('content')
<div x-data="{ activeSection: 'store', showAddUserModal: false }" class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900">Settings</h1>
        <p class="text-sm text-gray-500">Manage your store profile and user accounts</p>
    </div>

    {{-- Section Tabs --}}
    <div class="flex gap-2 overflow-x-auto scrollbar-hidden -mx-4 px-4 sm:mx-0 sm:px-0">
        <button @click="activeSection = 'store'"
                :class="activeSection === 'store' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            Store Profile
        </button>
        <button @click="activeSection = 'users'"
                :class="activeSection === 'users' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
                class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors">
            User Management
        </button>
    </div>

    {{-- SECTION: Store Profile --}}
    <div x-show="activeSection === 'store'" x-transition>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-body space-y-4">
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Store Information</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Store Name <span class="text-red-500">*</span></label>
                            <input type="text" name="store_name"
                                   value="{{ old('store_name', $tenant->name ?? '') }}"
                                   class="form-input @error('store_name') border-red-500 @enderror"
                                   placeholder="Your Pharmacy Name"
                                   required>
                            @error('store_name')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="form-label">Address</label>
                            <textarea name="address"
                                      class="form-input @error('address') border-red-500 @enderror"
                                      rows="2"
                                      placeholder="Full store address">{{ old('address', $tenant->address ?? '') }}</textarea>
                            @error('address')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone"
                                   value="{{ old('phone', $tenant->phone ?? '') }}"
                                   class="form-input"
                                   placeholder="Store phone number">
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="email"
                                   value="{{ old('email', $tenant->email ?? '') }}"
                                   class="form-input"
                                   placeholder="Store email">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-4">
                    <h2 class="text-base font-semibold text-gray-900 border-b border-gray-200 pb-2">Legal & Tax Details</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin"
                                   value="{{ old('gstin', $tenant->gstin ?? '') }}"
                                   class="form-input font-mono"
                                   placeholder="e.g., 22AAAAA0000A1Z5"
                                   maxlength="15">
                        </div>

                        <div>
                            <label class="form-label">Drug License Number</label>
                            <input type="text" name="drug_license"
                                   value="{{ old('drug_license', $tenant->drug_license ?? '') }}"
                                   class="form-input font-mono"
                                   placeholder="Drug license number">
                        </div>

                        <div>
                            <label class="form-label">FSSAI Number</label>
                            <input type="text" name="fssai_number"
                                   value="{{ old('fssai_number', $tenant->fssai_number ?? '') }}"
                                   class="form-input font-mono"
                                   placeholder="FSSAI number (if applicable)">
                        </div>

                        <div>
                            <label class="form-label">PAN Number</label>
                            <input type="text" name="pan"
                                   value="{{ old('pan', $tenant->pan ?? '') }}"
                                   class="form-input font-mono uppercase"
                                   placeholder="e.g., ABCDE1234F"
                                   maxlength="10">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- SECTION: User Management --}}
    <div x-show="activeSection === 'users'" x-transition x-cloak>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Users</h2>
                    <button @click="showAddUserModal = true" class="btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add User
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="hidden sm:table-cell">Status</th>
                                <th class="hidden sm:table-cell">Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($users ?? []) as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full flex-shrink-0">
                                            <span class="text-sm font-semibold text-blue-700">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="text-sm text-gray-600">{{ $user->email }}</td>
                                <td>
                                    @php
                                        $roleColors = [
                                            'owner' => 'badge-blue',
                                            'store_manager' => 'badge-green',
                                            'pharmacist' => 'badge-amber',
                                            'cashier' => 'badge-gray',
                                        ];
                                    @endphp
                                    <span class="{{ $roleColors[$user->role] ?? 'badge-gray' }}">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </td>
                                <td class="hidden sm:table-cell">
                                    @if($user->is_active ?? true)
                                    <span class="badge-green">Active</span>
                                    @else
                                    <span class="badge-red">Inactive</span>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell text-sm text-gray-500">
                                    {{ $user->last_login_at ? \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    @if(($user->role ?? '') !== 'owner')
                                    <div class="flex items-center gap-1">
                                        <button class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                                title="Edit User">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-xs text-gray-400">Owner</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500">No users found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Add User Modal --}}
        <div x-show="showAddUserModal" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true">
            <div x-show="showAddUserModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showAddUserModal = false"
                 class="fixed inset-0 bg-black/50"></div>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
                    <div x-show="showAddUserModal"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-0 sm:scale-95"
                         class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-xl">

                        <div class="sm:hidden flex justify-center pt-3 pb-1">
                            <div class="w-10 h-1 bg-gray-300 rounded-full"></div>
                        </div>

                        <div class="px-5 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Add New User</h3>
                        </div>

                        <form method="POST" action="{{ route('settings.users.store') }}" class="px-5 py-4 space-y-4">
                            @csrf
                            <div>
                                <label class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" class="form-input" placeholder="Full name" required>
                            </div>
                            <div>
                                <label class="form-label">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" class="form-input" placeholder="Email address" required>
                            </div>
                            <div>
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-input" placeholder="Phone number">
                            </div>
                            <div>
                                <label class="form-label">Role <span class="text-red-500">*</span></label>
                                <select name="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <option value="store_manager">Store Manager</option>
                                    <option value="pharmacist">Pharmacist</option>
                                    <option value="cashier">Cashier</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password" class="form-input" placeholder="Create password" required minlength="8">
                            </div>
                            <div>
                                <label class="form-label">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm password" required>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" @click="showAddUserModal = false" class="btn-secondary flex-1">Cancel</button>
                                <button type="submit" class="btn-primary flex-1">Create User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
