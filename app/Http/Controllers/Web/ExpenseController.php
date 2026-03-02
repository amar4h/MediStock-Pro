<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $expenses = Expense::with(['expenseCategory:id,name', 'createdBy:id,name'])
            ->when($request->filled('date_from'), fn ($q) => $q->where('expense_date', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('expense_date', '<=', $request->input('date_to')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('expenseCategory', fn ($c) => $c->where('name', $request->input('category'))))
            ->when($request->filled('payment_mode'), fn ($q) => $q->where('payment_mode', $request->input('payment_mode')))
            ->orderByDesc('expense_date')
            ->paginate(15)
            ->withQueryString();

        $monthlyTotal = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $lastMonthTotal = Expense::whereMonth('expense_date', now()->subMonth()->month)
            ->whereYear('expense_date', now()->subMonth()->year)
            ->sum('amount');

        $transactionCount = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->count();

        $topCategory = Expense::with('expenseCategory')
            ->whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->orderByDesc('total')
            ->first()?->expenseCategory?->name ?? 'N/A';

        $categories = ExpenseCategory::orderBy('name')->get(['id', 'name']);

        return view('pages.expenses.index', compact(
            'expenses', 'monthlyTotal', 'lastMonthTotal', 'transactionCount', 'topCategory', 'categories'
        ));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        Expense::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        return redirect()->route('expenses.index')->with('success', 'Expense added successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }
}
