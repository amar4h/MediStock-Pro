<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * List expenses (paginated, date filterable).
     */
    public function index(Request $request): JsonResponse
    {
        $expenses = Expense::with([
            'expenseCategory:id,name',
            'createdBy:id,name',
        ])
            ->when($request->filled('expense_category_id'), function ($q) use ($request) {
                $q->where('expense_category_id', $request->input('expense_category_id'));
            })
            ->when($request->filled('from'), function ($q) use ($request) {
                $q->where('expense_date', '>=', $request->input('from'));
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $q->where('expense_date', '<=', $request->input('to'));
            })
            ->when($request->filled('payment_mode'), function ($q) use ($request) {
                $q->where('payment_mode', $request->input('payment_mode'));
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $expenses->items(),
            'meta'    => [
                'current_page' => $expenses->currentPage(),
                'last_page'    => $expenses->lastPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
            ],
        ]);
    }

    /**
     * Create a new expense.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = Expense::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data'    => $expense->load(['expenseCategory:id,name', 'createdBy:id,name']),
        ], 201);
    }

    /**
     * Get a specific expense.
     */
    public function show(int $id): JsonResponse
    {
        $expense = Expense::with(['expenseCategory:id,name', 'createdBy:id,name'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $expense,
        ]);
    }

    /**
     * Update an expense.
     */
    public function update(UpdateExpenseRequest $request, int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $expense->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data'    => $expense->fresh()->load(['expenseCategory:id,name', 'createdBy:id,name']),
        ]);
    }

    /**
     * Delete an expense.
     */
    public function destroy(int $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }
}
