<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Get comprehensive dashboard data.
     */
    public function index(): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get dashboard stats (KPIs) — lightweight endpoint for quick refresh.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * Get dashboard alerts (low stock, near expiry, outstanding payments).
     */
    public function alerts(): JsonResponse
    {
        $alerts = $this->dashboardService->getAlerts();

        return response()->json([
            'success' => true,
            'data'    => $alerts,
        ]);
    }
}
