<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    /**
     * Sales report (daily/weekly/monthly/annual).
     */
    public function sales(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,annual',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->salesReport(
            $validated['period'] ?? 'daily',
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }

    /**
     * Profit report (gross profit from sales).
     */
    public function profit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:daily,weekly,monthly,annual',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->profitReport(
            $validated['period'] ?? 'daily',
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }

    /**
     * Net profit report (sales profit - expenses).
     */
    public function netProfit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->netProfitReport(
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }

    /**
     * Expense report (category-wise breakdown).
     */
    public function expenses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->expenseReport(
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }

    /**
     * GST summary report.
     */
    public function gstSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->gstSummaryReport(
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }

    /**
     * Item-wise profit breakdown.
     */
    public function itemWiseProfit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $report = $this->reportService->itemWiseProfitReport(
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json([
            'success' => true,
            'data'    => $report,
        ]);
    }
}
