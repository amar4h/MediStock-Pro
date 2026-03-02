<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public / Guest routes ─────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', [AuthController::class, 'register']);
});

// ── Authenticated routes ──────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return view('pages.dashboard.index');
    })->name('dashboard');

    Route::get('/items', function () {
        return view('pages.items.index');
    })->name('items.index');

    Route::get('/items/create', function () {
        return view('pages.items.create');
    })->name('items.create');

    Route::get('/purchases', function () {
        return view('pages.purchases.index');
    })->name('purchases.index');

    Route::get('/purchases/create', function () {
        return view('pages.purchases.create');
    })->name('purchases.create');

    Route::get('/sales', function () {
        return view('pages.sales.index');
    })->name('sales.index');

    Route::get('/sales/create', function () {
        return view('pages.sales.create');
    })->name('sales.create');

    Route::get('/inventory', function () {
        return view('pages.inventory.index');
    })->name('inventory.index');

    Route::get('/reports', function () {
        return view('pages.reports.index');
    })->name('reports.index');

    Route::get('/customers', function () {
        return view('pages.customers.index');
    })->name('customers.index');

    Route::get('/suppliers', function () {
        return view('pages.suppliers.index');
    })->name('suppliers.index');

    Route::get('/expenses', function () {
        return view('pages.expenses.index');
    })->name('expenses.index');

    Route::get('/settings', function () {
        return view('pages.settings.index');
    })->name('settings.index');
});
