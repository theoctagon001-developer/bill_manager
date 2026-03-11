<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $budgets = Budget::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate($perPage);

        $data = collect($budgets->items())->map(function (Budget $budget) {
            return $this->transformBudget($budget);
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $budgets->currentPage(),
                'per_page' => $budgets->PerPage(),
                'total' => $budgets->total(),
                'last_page' => $budgets->lastPage(),
                'from' => $budgets->firstItem(),
                'to' => $budgets->lastItem(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:1970|max:2100',
            ]);
            $budget = Budget::where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->firstOrFail();
            return response()->json([
                'success' => true,
                'data' => $this->transformBudget($budget),
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
                'message' => 'Budget not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:1970|max:2100',
                'expense' => 'required|numeric|min:0',
            ]);
            $exists = Budget::where('month', $validated['month'])
                ->where('year', $validated['year'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget for month/year already exists',
                ], 422);
            }
            $saving = (float) $validated['amount'] - (float) $validated['expense'];
            $budget = Budget::create([
                'amount' => $validated['amount'],
                'month' => $validated['month'],
                'year' => $validated['year'],
                'expense_assumed' => $validated['expense'],
                'saving_assumed' => $saving < 0 ? 0 : $saving,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Budget created successfully',
                'data' => $this->transformBudget($budget),
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
                'message' => 'Failed to create budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $budget = Budget::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $this->transformBudget($budget),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $budget = Budget::findOrFail($id);
            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0',
                'month' => 'sometimes|integer|min:1|max:12',
                'year' => 'sometimes|integer|min:1970|max:2100',
                'expense' => 'sometimes|numeric|min:0',
                'expense_assumed' => 'sometimes|numeric|min:0',
            ]);
            if (! empty($validated)) {
                $payload = $validated;
                if (isset($payload['expense'])) {
                    $payload['expense_assumed'] = $payload['expense'];
                    unset($payload['expense']);
                }
                if (array_key_exists('amount', $payload) || array_key_exists('expense_assumed', $payload)) {
                    $amt = (float) ($payload['amount'] ?? $budget->amount);
                    $exp = (float) ($payload['expense_assumed'] ?? $budget->expense_assumed);
                    $payload['saving_assumed'] = max($amt - $exp, 0);
                }
                $budget->update($payload);
            }
            return response()->json([
                'success' => true,
                'message' => 'Budget updated successfully',
                'data' => $this->transformBudget($budget->fresh()),
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
                'message' => 'Budget not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $budget = Budget::findOrFail($id);
            $budget->forceDelete();
            return response()->json([
                'success' => true,
                'message' => 'Budget deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete budget',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function transformBudget(Budget $budget): array
    {
        $expenses = Expense::where('year', $budget->year)
            ->where('month', $budget->month)
            ->whereNull('deleted_at')
            ->orderBy('spend_date', 'desc')
            ->get();
        $actualExpense = (float) $expenses->sum('amount');
        $expenseFromSaving = max($actualExpense - (float) $budget->expense_assumed, 0);
        $exceededBudget = $actualExpense > (float) $budget->amount;

        $grouped = $expenses->groupBy('category')->map(function ($items, $category) {
            return [
                'category' => $category,
                'total_expense' => number_format((float) $items->sum('amount'), 2, '.', ''),
                'count' => $items->count(),
            ];
        })->values();

        $items = $expenses->map(function (Expense $e) {
            return [
                'id' => $e->id,
                'amount' => (string) $e->amount,
                'category' => $e->category,
                'spend_date' => $e->spend_date ? $e->spend_date->toDateString() : null,
                'month' => $e->month,
                'year' => $e->year,
            ];
        })->values();

        return [
            'id' => $budget->id,
            'month' => $budget->month,
            'year' => $budget->year,
            'budget' => (string) $budget->amount,
            'expense_assumed' => (string) $budget->expense_assumed,
            'saving_assumed' => (string) $budget->saving_assumed,
            'summary' => [
                'actual_expense' => number_format($actualExpense, 2, '.', ''),
                'expense_from_saving' => number_format($expenseFromSaving, 2, '.', ''),
                'does_exceeded_the_budget' => $exceededBudget,
            ],
            'expenses' => [
                'categories' => $grouped,
                'items' => $items,
            ],
        ];
    }
}
