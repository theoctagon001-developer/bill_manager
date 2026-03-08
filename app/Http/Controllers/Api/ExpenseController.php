<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $month = $request->query('month');
        $year = $request->query('year');
        $category = $request->query('category');
        $query = Expense::query()->whereNull('deleted_at');
        if ($month) {
            $query->where('month', (int) $month);
        }
        if ($year) {
            $query->where('year', (int) $year);
        }
        if ($category) {
            $query->where('category', $category);
        }
        $expenses = $query->orderBy('spend_date', 'desc')->paginate($perPage);
        $data = collect($expenses->items())->map(function (Expense $e) {
            return [
                'id' => $e->id,
                'amount' => (string) $e->amount,
                'category' => $e->category,
                'spend_date' => $e->spend_date ? $e->spend_date->toDateString() : null,
                'month' => $e->month,
                'year' => $e->year,
                'budget_id' => $e->budget_id,
            ];
        })->values();
        $grouped = collect($expenses->items())->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'total_expense' => number_format((float) collect($items)->sum('amount'), 2, '.', ''),
                'count' => collect($items)->count(),
            ];
        })->values();
        return response()->json([
            'success' => true,
            'data' => $data,
            'grouped' => $grouped,
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'per_page' => $expenses->PerPage(),
                'total' => $expenses->total(),
                'last_page' => $expenses->lastPage(),
                'from' => $expenses->firstItem(),
                'to' => $expenses->lastItem(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:1970|max:2100',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);
            $perPage = (int) ($validated['per_page'] ?? 15);
            $expenses = Expense::whereNull('deleted_at')
                ->where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->orderBy('spend_date', 'desc')
                ->paginate($perPage);
            $data = collect($expenses->items())->map(function (Expense $e) {
                return [
                    'id' => $e->id,
                    'amount' => (string) $e->amount,
                    'category' => $e->category,
                    'spend_date' => $e->spend_date ? $e->spend_date->toDateString() : null,
                    'month' => $e->month,
                    'year' => $e->year,
                    'budget_id' => $e->budget_id,
                ];
            })->values();
            $grouped = collect($expenses->items())->groupBy('category')->map(function ($items, $cat) {
                return [
                    'category' => $cat,
                    'total_expense' => number_format((float) collect($items)->sum('amount'), 2, '.', ''),
                    'count' => collect($items)->count(),
                ];
            })->values();
            return response()->json([
                'success' => true,
                'data' => $data,
                'grouped' => $grouped,
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'per_page' => $expenses->PerPage(),
                    'total' => $expenses->total(),
                    'last_page' => $expenses->lastPage(),
                    'from' => $expenses->firstItem(),
                    'to' => $expenses->lastItem(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search expenses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'category' => 'required|string|max:255',
                'spend_date' => 'required|date',
                'month' => 'sometimes|integer|min:1|max:12',
                'year' => 'sometimes|integer|min:1970|max:2100',
                'budget_id' => 'sometimes|nullable|integer|exists:budgets,id',
            ]);
            $date = new \Carbon\Carbon($validated['spend_date']);
            $month = (int) ($validated['month'] ?? $date->month);
            $year = (int) ($validated['year'] ?? $date->year);
            $budgetId = $validated['budget_id'] ?? null;
            if (! $budgetId) {
                $budget = Budget::where('month', $month)->where('year', $year)->first();
                $budgetId = $budget?->id;
            }
            $expense = Expense::create([
                'amount' => $validated['amount'],
                'category' => $validated['category'],
                'spend_date' => $validated['spend_date'],
                'month' => $month,
                'year' => $year,
                'budget_id' => $budgetId,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully',
                'data' => [
                    'id' => $expense->id,
                    'amount' => (string) $expense->amount,
                    'category' => $expense->category,
                    'spend_date' => $expense->spend_date ? $expense->spend_date->toDateString() : null,
                    'month' => $expense->month,
                    'year' => $expense->year,
                    'budget_id' => $expense->budget_id,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $e = Expense::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $e->id,
                    'amount' => (string) $e->amount,
                    'category' => $e->category,
                    'spend_date' => $e->spend_date ? $e->spend_date->toDateString() : null,
                    'month' => $e->month,
                    'year' => $e->year,
                    'budget_id' => $e->budget_id,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);
            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0',
                'category' => 'sometimes|string|max:255',
                'spend_date' => 'sometimes|date',
                'month' => 'sometimes|integer|min:1|max:12',
                'year' => 'sometimes|integer|min:1970|max:2100',
                'budget_id' => 'sometimes|nullable|integer|exists:budgets,id',
            ]);
            if (! empty($validated)) {
                $expense->update($validated);
            }
            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => [
                    'id' => $expense->id,
                    'amount' => (string) $expense->amount,
                    'category' => $expense->category,
                    'spend_date' => $expense->spend_date ? $expense->spend_date->toDateString() : null,
                    'month' => $expense->month,
                    'year' => $expense->year,
                    'budget_id' => $expense->budget_id,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $expense = Expense::findOrFail($id);
            $expense->forceDelete();
            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
