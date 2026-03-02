<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\DashboardService;
use App\Services\StockService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly StockService $stockService,
    ) {}

    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;
        $data = $this->dashboardService->getDashboardData($tenantId);

        $recentSales = Sale::with(['customer:id,name', 'saleItems'])
            ->withCount('saleItems')
            ->whereDate('invoice_date', today())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $lowStockItems = $this->stockService->getLowStockItems($tenantId)->take(5);
        $nearExpiryBatches = $this->stockService->getNearExpiryBatches($tenantId, 90)->take(5);

        return view('pages.dashboard.index', [
            'todaySales'        => $data['todaySales'],
            'todayProfit'       => $data['todayProfit'],
            'outstandingCredit' => $data['outstandingCustomerCredit'],
            'pendingPayments'   => $data['pendingSupplierPayment'],
            'lowStockCount'     => $data['lowStockCount'],
            'nearExpiryCount'   => $data['nearExpiryCount'],
            'salesCount'        => $recentSales->count(),
            'creditCustomers'   => 0,
            'recentSales'       => $recentSales,
            'lowStockItems'     => $lowStockItems,
            'nearExpiryBatches' => $nearExpiryBatches,
        ]);
    }
}
