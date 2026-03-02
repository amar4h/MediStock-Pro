<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $tenantId    = auth()->user()->tenant_id;
        $reportType  = $request->input('report');
        $reportData  = null;
        $reportSummary  = null;
        $reportColumns  = [];

        if ($reportType) {
            $from   = $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : null;
            $to     = $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : null;
            $period = $request->input('period', 'daily');

            [$reportData, $reportSummary, $reportColumns] = match ($reportType) {
                'sales'       => $this->buildSalesReport($tenantId, $period, $from, $to),
                'profit'      => $this->buildProfitReport($tenantId, $period, $from, $to),
                'net-profit'  => $this->buildNetProfitReport($tenantId, $from, $to),
                'expenses'    => $this->buildExpenseReport($tenantId, $from, $to),
                'gst'         => $this->buildGstReport($tenantId, $from, $to),
                'item-profit' => $this->buildItemProfitReport($tenantId, $from, $to),
                default       => [collect(), null, []],
            };
        }

        return view('pages.reports.index', compact('reportData', 'reportSummary', 'reportColumns'));
    }

    private function buildSalesReport(int $tenantId, string $period, ?Carbon $from, ?Carbon $to): array
    {
        $data = $this->reportService->salesReport($tenantId, $period, $from, $to);

        $summary = [
            'Total Sales'    => '₹' . number_format($data->sum('total_sales'), 2),
            'Total Invoices' => $data->sum('total_invoices'),
            'Total GST'      => '₹' . number_format($data->sum('total_gst'), 2),
            'Total Discount' => '₹' . number_format($data->sum('total_discount'), 2),
        ];

        $columns = [
            ['key' => 'period',         'label' => 'Period',       'class' => ''],
            ['key' => 'total_invoices', 'label' => 'Invoices',     'class' => 'text-right'],
            ['key' => 'total_sales',    'label' => 'Sales Amount', 'class' => 'text-right'],
            ['key' => 'total_gst',      'label' => 'GST',          'class' => 'text-right'],
            ['key' => 'total_discount', 'label' => 'Discounts',    'class' => 'text-right'],
        ];

        return [$data, $summary, $columns];
    }

    private function buildProfitReport(int $tenantId, string $period, ?Carbon $from, ?Carbon $to): array
    {
        $data = $this->reportService->profitReport($tenantId, $period, $from, $to);

        $summary = [
            'Total Revenue' => '₹' . number_format($data->sum('total_revenue'), 2),
            'Total Cost'    => '₹' . number_format($data->sum('total_cost'), 2),
            'Gross Profit'  => '₹' . number_format($data->sum('gross_profit'), 2),
        ];

        $columns = [
            ['key' => 'period',               'label' => 'Period',    'class' => ''],
            ['key' => 'total_revenue',        'label' => 'Revenue',   'class' => 'text-right'],
            ['key' => 'total_cost',           'label' => 'Cost',      'class' => 'text-right'],
            ['key' => 'gross_profit',         'label' => 'Profit',    'class' => 'text-right'],
            ['key' => 'profit_margin_percent', 'label' => 'Margin %', 'class' => 'text-right'],
        ];

        return [$data, $summary, $columns];
    }

    private function buildNetProfitReport(int $tenantId, ?Carbon $from, ?Carbon $to): array
    {
        $result = $this->reportService->netProfitReport($tenantId, $from, $to);

        $summary = [
            'Total Revenue'  => '₹' . number_format($result['total_revenue'], 2),
            'Total Cost'     => '₹' . number_format($result['total_cost'], 2),
            'Gross Profit'   => '₹' . number_format($result['gross_profit'], 2),
            'Total Expenses' => '₹' . number_format($result['total_expenses'], 2),
            'Net Profit'     => '₹' . number_format($result['net_profit'], 2),
        ];

        // Net profit is a single-row summary, present as collection for the table
        $data = collect([
            (object) [
                'metric'   => 'Revenue',
                'amount'   => '₹' . number_format($result['total_revenue'], 2),
            ],
            (object) [
                'metric'   => 'Cost of Goods',
                'amount'   => '₹' . number_format($result['total_cost'], 2),
            ],
            (object) [
                'metric'   => 'Gross Profit',
                'amount'   => '₹' . number_format($result['gross_profit'], 2),
            ],
            (object) [
                'metric'   => 'Expenses',
                'amount'   => '₹' . number_format($result['total_expenses'], 2),
            ],
            (object) [
                'metric'   => 'Net Profit',
                'amount'   => '₹' . number_format($result['net_profit'], 2),
            ],
        ]);

        $columns = [
            ['key' => 'metric', 'label' => 'Metric', 'class' => 'font-semibold'],
            ['key' => 'amount', 'label' => 'Amount', 'class' => 'text-right'],
        ];

        return [$data, $summary, $columns];
    }

    private function buildExpenseReport(int $tenantId, ?Carbon $from, ?Carbon $to): array
    {
        $data = $this->reportService->expenseReport($tenantId, $from, $to);

        $summary = [
            'Total Expenses' => '₹' . number_format($data->sum('total_amount'), 2),
            'Categories'     => $data->count(),
        ];

        $columns = [
            ['key' => 'category_name', 'label' => 'Category',     'class' => ''],
            ['key' => 'total_entries', 'label' => 'Transactions', 'class' => 'text-right'],
            ['key' => 'total_amount',  'label' => 'Amount',       'class' => 'text-right'],
        ];

        return [$data, $summary, $columns];
    }

    private function buildGstReport(int $tenantId, ?Carbon $from, ?Carbon $to): array
    {
        $result = $this->reportService->gstSummary($tenantId, $from, $to);

        $summary = [
            'GST Collected'      => '₹' . number_format($result['total_gst_collected'], 2),
            'GST Paid'           => '₹' . number_format($result['total_gst_paid'], 2),
            'Net GST Liability'  => '₹' . number_format($result['net_gst_liability'], 2),
        ];

        // Merge collected and paid into a combined table
        $rows = collect();
        foreach ($result['gst_collected'] as $row) {
            $rows->push((object) [
                'type'            => 'Collected (Sales)',
                'gst_percent'     => $row->gst_percent . '%',
                'taxable_amount'  => number_format((float) $row->taxable_amount, 2),
                'total_gst'       => number_format((float) $row->total_gst, 2),
            ]);
        }
        foreach ($result['gst_paid'] as $row) {
            $rows->push((object) [
                'type'            => 'Paid (Purchases)',
                'gst_percent'     => $row->gst_percent . '%',
                'taxable_amount'  => number_format((float) $row->taxable_amount, 2),
                'total_gst'       => number_format((float) $row->total_gst, 2),
            ]);
        }

        $columns = [
            ['key' => 'type',           'label' => 'Type',           'class' => ''],
            ['key' => 'gst_percent',    'label' => 'GST Rate',      'class' => 'text-right'],
            ['key' => 'taxable_amount', 'label' => 'Taxable Amount', 'class' => 'text-right'],
            ['key' => 'total_gst',      'label' => 'GST Amount',    'class' => 'text-right'],
        ];

        return [$rows, $summary, $columns];
    }

    private function buildItemProfitReport(int $tenantId, ?Carbon $from, ?Carbon $to): array
    {
        $data = $this->reportService->itemWiseProfit($tenantId, $from, $to);

        $summary = [
            'Total Revenue' => '₹' . number_format($data->sum('total_revenue'), 2),
            'Total Profit'  => '₹' . number_format($data->sum('profit'), 2),
            'Items Sold'    => $data->count(),
        ];

        $columns = [
            ['key' => 'item_name',           'label' => 'Item',      'class' => ''],
            ['key' => 'total_quantity_sold',  'label' => 'Qty Sold',  'class' => 'text-right'],
            ['key' => 'total_revenue',        'label' => 'Revenue',   'class' => 'text-right'],
            ['key' => 'total_cost',           'label' => 'Cost',      'class' => 'text-right'],
            ['key' => 'profit',              'label' => 'Profit',    'class' => 'text-right'],
            ['key' => 'margin_percent',      'label' => 'Margin %',  'class' => 'text-right'],
        ];

        return [$data, $summary, $columns];
    }
}
