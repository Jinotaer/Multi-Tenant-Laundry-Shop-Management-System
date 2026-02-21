<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): View
    {
        $expenses = Expense::query()
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->month, function ($q) use ($request) {
                $month = $request->month;  // Format: 2026-02
                $q->whereYear('expense_date', substr($month, 0, 4))
                  ->whereMonth('expense_date', substr($month, 5, 2));
            })
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $categories = Expense::categoryLabels();
        $totalAmount = Expense::sum('amount');
        $monthlyTotal = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        return view('tenant.expenses.index', compact('expenses', 'categories', 'totalAmount', 'monthlyTotal'));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): View
    {
        $categories = Expense::categoryLabels();

        return view('tenant.expenses.create', compact('categories'));
    }

    /**
     * Store a newly created expense.
     */
    public function store(ExpenseRequest $request): RedirectResponse
    {
        Expense::create($request->validated());

        return redirect()->route('tenant.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    /**
     * Show the form for editing an expense.
     */
    public function edit(Expense $expense): View
    {
        $categories = Expense::categoryLabels();

        return view('tenant.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the specified expense.
     */
    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('tenant.expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('tenant.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}

