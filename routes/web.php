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

    // Dashboard
    Route::get('/dashboard', function () {
        return view('pages.dashboard.index');
    })->name('dashboard');

    // Items
    Route::get('/items', function () {
        return view('pages.items.index');
    })->name('items.index');
    Route::get('/items/create', function () {
        return view('pages.items.create');
    })->name('items.create');
    Route::post('/items', function () {
        return back()->with('info', 'Item creation coming soon.');
    })->name('items.store');
    Route::get('/items/{item}', function ($item) {
        return back();
    })->name('items.show');
    Route::put('/items/{item}', function ($item) {
        return back()->with('info', 'Item update coming soon.');
    })->name('items.update');

    // Purchases
    Route::get('/purchases', function () {
        return view('pages.purchases.index');
    })->name('purchases.index');
    Route::get('/purchases/create', function () {
        return view('pages.purchases.create');
    })->name('purchases.create');

    // Sales
    Route::get('/sales', function () {
        return view('pages.sales.index');
    })->name('sales.index');
    Route::get('/sales/create', function () {
        return view('pages.sales.create');
    })->name('sales.create');
    Route::get('/sales/{sale}', function ($sale) {
        return back();
    })->name('sales.show');

    // Inventory
    Route::get('/inventory', function () {
        return view('pages.inventory.index');
    })->name('inventory.index');

    // Reports
    Route::get('/reports', function () {
        return view('pages.reports.index');
    })->name('reports.index');

    // Customers
    Route::get('/customers', function () {
        return view('pages.customers.index');
    })->name('customers.index');
    Route::post('/customers', function () {
        return back()->with('info', 'Customer creation coming soon.');
    })->name('customers.store');

    // Suppliers
    Route::get('/suppliers', function () {
        return view('pages.suppliers.index');
    })->name('suppliers.index');
    Route::post('/suppliers', function () {
        return back()->with('info', 'Supplier creation coming soon.');
    })->name('suppliers.store');

    // Expenses
    Route::get('/expenses', function () {
        return view('pages.expenses.index');
    })->name('expenses.index');
    Route::post('/expenses', function () {
        return back()->with('info', 'Expense creation coming soon.');
    })->name('expenses.store');

    // Settings
    Route::get('/settings', function () {
        return view('pages.settings.index');
    })->name('settings.index');
    Route::post('/settings', function () {
        return back()->with('info', 'Settings update coming soon.');
    })->name('settings.update');
    Route::post('/settings/users', function () {
        return back()->with('info', 'User creation coming soon.');
    })->name('settings.users.store');
});
