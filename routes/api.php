<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TenantSettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ManufacturerController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierLedgerController;
use App\Http\Controllers\Api\SupplierPaymentController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerLedgerController;
use App\Http\Controllers\Api\CustomerPaymentController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleReturnController;
use App\Http\Controllers\Api\SaleInvoiceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\DiscardController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceScanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api by default and use the api
| middleware group. Authentication routes are public; all others
| require auth:sanctum + tenant middleware.
|
*/

// ──────────────────────────────────────────────
// Public Auth Routes (no authentication required)
// ──────────────────────────────────────────────
Route::prefix('v1/auth')->as('api.v1.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});

// ──────────────────────────────────────────────
// Authenticated Routes (require auth:sanctum)
// ──────────────────────────────────────────────
Route::prefix('v1')->as('api.v1.')->middleware(['auth:sanctum'])->group(function () {

    // ── Auth (authenticated) ────────────────────
    Route::prefix('auth')->as('auth.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::put('/password', [AuthController::class, 'updatePassword'])->name('update-password');
    });

    // ── All tenant-scoped routes ────────────────
    Route::middleware(['tenant'])->group(function () {

        // ── Tenant Settings ─────────────────────
        Route::prefix('tenant')->as('tenant.')->group(function () {
            Route::get('/settings', [TenantSettingController::class, 'show'])->name('settings.show');
            Route::put('/settings', [TenantSettingController::class, 'update'])->name('settings.update');
        });

        // ── Users ───────────────────────────────
        Route::apiResource('users', UserController::class);

        // ── Roles ───────────────────────────────
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');

        // ── Items ───────────────────────────────
        Route::prefix('items')->as('items.')->group(function () {
            Route::get('/search', [ItemController::class, 'search'])->name('search');
            Route::get('/barcode/{barcode}', [ItemController::class, 'lookupBarcode'])->name('barcode-lookup');
        });
        Route::apiResource('items', ItemController::class);

        // ── Categories ──────────────────────────
        Route::apiResource('categories', CategoryController::class);

        // ── Manufacturers ───────────────────────
        Route::apiResource('manufacturers', ManufacturerController::class);

        // ── Batches ─────────────────────────────
        Route::prefix('batches')->as('batches.')->group(function () {
            Route::get('/', [BatchController::class, 'index'])->name('index');
            Route::get('/near-expiry', [BatchController::class, 'nearExpiry'])->name('near-expiry');
            Route::get('/expired', [BatchController::class, 'expired'])->name('expired');
            Route::get('/{batch}', [BatchController::class, 'show'])->name('show');
        });

        // ── Suppliers ───────────────────────────
        Route::apiResource('suppliers', SupplierController::class);
        Route::prefix('suppliers/{supplier}')->as('suppliers.')->group(function () {
            Route::get('/ledger', [SupplierLedgerController::class, 'index'])->name('ledger.index');
            Route::get('/ledger/balance', [SupplierLedgerController::class, 'balance'])->name('ledger.balance');
            Route::get('/payments', [SupplierPaymentController::class, 'index'])->name('payments.index');
            Route::post('/payments', [SupplierPaymentController::class, 'store'])->name('payments.store');
            Route::get('/payments/{payment}', [SupplierPaymentController::class, 'show'])->name('payments.show');
        });

        // ── Purchases ───────────────────────────
        Route::apiResource('purchases', PurchaseController::class);
        Route::prefix('purchases/{purchase}')->as('purchases.')->group(function () {
            Route::post('/returns', [PurchaseReturnController::class, 'store'])->name('returns.store');
            Route::get('/returns', [PurchaseReturnController::class, 'index'])->name('returns.index');
        });
        Route::get('/purchase-returns', [PurchaseReturnController::class, 'all'])->name('purchase-returns.index');

        // ── Customers ───────────────────────────
        Route::apiResource('customers', CustomerController::class);
        Route::prefix('customers/{customer}')->as('customers.')->group(function () {
            Route::get('/ledger', [CustomerLedgerController::class, 'index'])->name('ledger.index');
            Route::get('/ledger/balance', [CustomerLedgerController::class, 'balance'])->name('ledger.balance');
            Route::get('/payments', [CustomerPaymentController::class, 'index'])->name('payments.index');
            Route::post('/payments', [CustomerPaymentController::class, 'store'])->name('payments.store');
            Route::get('/payments/{payment}', [CustomerPaymentController::class, 'show'])->name('payments.show');
        });

        // ── Sales ───────────────────────────────
        Route::apiResource('sales', SaleController::class);
        Route::prefix('sales/{sale}')->as('sales.')->group(function () {
            Route::post('/returns', [SaleReturnController::class, 'store'])->name('returns.store');
            Route::get('/returns', [SaleReturnController::class, 'index'])->name('returns.index');
            Route::get('/invoice', [SaleInvoiceController::class, 'show'])->name('invoice.show');
            Route::get('/invoice/download', [SaleInvoiceController::class, 'download'])->name('invoice.download');
        });
        Route::get('/sale-returns', [SaleReturnController::class, 'all'])->name('sale-returns.index');

        // ── Inventory ───────────────────────────
        Route::prefix('inventory')->as('inventory.')->group(function () {
            Route::get('/stock', [InventoryController::class, 'stock'])->name('stock');
            Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
            Route::get('/near-expiry', [InventoryController::class, 'nearExpiry'])->name('near-expiry');
            Route::get('/expired', [InventoryController::class, 'expired'])->name('expired');
            Route::get('/dead-stock', [InventoryController::class, 'deadStock'])->name('dead-stock');
            Route::get('/movement-analysis', [InventoryController::class, 'movementAnalysis'])->name('movement-analysis');

            // Discards
            Route::get('/discards', [DiscardController::class, 'index'])->name('discards.index');
            Route::post('/discards', [DiscardController::class, 'store'])->name('discards.store');
            Route::get('/discards/{discard}', [DiscardController::class, 'show'])->name('discards.show');

            // Stock Movements
            Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
            Route::get('/movements/{movement}', [StockMovementController::class, 'show'])->name('movements.show');
        });

        // ── Expenses ────────────────────────────
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('expense-categories', ExpenseCategoryController::class);

        // ── Reports ─────────────────────────────
        Route::prefix('reports')->as('reports.')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/profit', [ReportController::class, 'profit'])->name('profit');
            Route::get('/net-profit', [ReportController::class, 'netProfit'])->name('net-profit');
            Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
            Route::get('/gst-summary', [ReportController::class, 'gstSummary'])->name('gst-summary');
            Route::get('/item-wise-profit', [ReportController::class, 'itemWiseProfit'])->name('item-wise-profit');
        });

        // ── Dashboard ───────────────────────────
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');

        // ── Invoice Scans ───────────────────────
        Route::apiResource('invoice-scans', InvoiceScanController::class);
        Route::prefix('invoice-scans/{invoiceScan}')->as('invoice-scans.')->group(function () {
            Route::post('/process', [InvoiceScanController::class, 'process'])->name('process');
            Route::post('/confirm', [InvoiceScanController::class, 'confirm'])->name('confirm');
            Route::get('/result', [InvoiceScanController::class, 'result'])->name('result');
        });
    });
});
