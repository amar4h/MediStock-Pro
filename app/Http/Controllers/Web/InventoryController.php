<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Contracts\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
    ) {}

    public function index(): View
    {
        $tenantId = auth()->user()->tenant_id;

        $stockSummary = Item::with(['category:id,name'])
            ->withSum('batches as total_stock', 'stock_quantity')
            ->withCount('batches')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $lowStockItems     = $this->stockService->getLowStockItems($tenantId);
        $nearExpiryBatches = $this->stockService->getNearExpiryBatches($tenantId, 90);
        $expiredBatches    = $this->stockService->getExpiredBatches($tenantId);
        $deadStockItems    = $this->stockService->getDeadStock($tenantId, 90);

        return view('pages.inventory.index', [
            'totalItems'        => Item::where('is_active', true)->count(),
            'lowStockCount'     => $lowStockItems->count(),
            'nearExpiryCount'   => $nearExpiryBatches->count(),
            'expiredCount'      => $expiredBatches->count(),
            'deadStockCount'    => $deadStockItems->count(),
            'stockSummary'      => $stockSummary,
            'lowStockItems'     => $lowStockItems,
            'nearExpiryBatches' => $nearExpiryBatches,
            'expiredBatches'    => $expiredBatches,
            'deadStockItems'    => $deadStockItems,
        ]);
    }
}
