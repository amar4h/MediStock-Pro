<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    /**
     * Create a sale return for a specific sale.
     */
    public function store(StoreSaleReturnRequest $request, Sale $sale): JsonResponse
    {
        $saleReturn = $this->saleService->createSaleReturn(
            $sale,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sale return created successfully.',
            'data'    => $saleReturn->load([
                'sale:id,invoice_number,customer_id',
                'saleReturnItems.item:id,name',
                'saleReturnItems.batch:id,batch_number',
                'createdBy:id,name',
            ]),
        ], 201);
    }

    /**
     * List returns for a specific sale.
     */
    public function index(Sale $sale): JsonResponse
    {
        $returns = $sale->saleReturns()
            ->with([
                'saleReturnItems.item:id,name',
                'saleReturnItems.batch:id,batch_number',
                'createdBy:id,name',
            ])
            ->orderByDesc('return_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $returns,
        ]);
    }

    /**
     * List all sale returns across all sales (tenant-scoped).
     */
    public function all(Request $request): JsonResponse
    {
        $returns = SaleReturn::with([
            'sale:id,invoice_number,customer_id',
            'sale.customer:id,name',
            'saleReturnItems.item:id,name',
            'createdBy:id,name',
        ])
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('return_date', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('return_date', '<=', $request->input('to'));
            })
            ->orderByDesc('return_date')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $returns->items(),
            'meta'    => [
                'current_page' => $returns->currentPage(),
                'last_page'    => $returns->lastPage(),
                'per_page'     => $returns->perPage(),
                'total'        => $returns->total(),
            ],
        ]);
    }
}
