<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::get('/register', function () {
    return view('auth.register');
})->name('register')->middleware('guest');

// Authenticated app shell (SPA-like with Blade + Alpine.js)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/dashboard', function () {
        return view('app.dashboard');
    })->name('dashboard');

    // All app routes render the same shell; Alpine.js handles client-side routing
    Route::get('/items', function () {
        return view('app.items.index');
    })->name('items.index');

    Route::get('/purchases', function () {
        return view('app.purchases.index');
    })->name('purchases.index');

    Route::get('/sales', function () {
        return view('app.sales.index');
    })->name('sales.index');

    Route::get('/inventory', function () {
        return view('app.inventory.index');
    })->name('inventory.index');

    Route::get('/reports', function () {
        return view('app.reports.index');
    })->name('reports.index');

    Route::get('/settings', function () {
        return view('app.settings.index');
    })->name('settings.index');
});
