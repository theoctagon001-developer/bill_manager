<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $monthParam = $request->query('month');
        $yearParam = $request->query('year');
        $isFiltered = !is_null($monthParam) || !is_null($yearParam);

        $currentMonth = (int) ($monthParam ?? Carbon::now()->month);
        $currentYear = (int) ($yearParam ?? Carbon::now()->year);

        if (!$isFiltered) {
            $budgets = Budget::where('year', $currentYear)
                ->where('month', '<=', $currentMonth)
                ->get();
            $expenses = Expense::whereNull('deleted_at')
                ->where('year', $currentYear)
                ->where('month', '<=', $currentMonth)
                ->orderBy('spend_date', 'desc')
                ->get();

            $salary = (float) $budgets->sum('amount');
            $expenseAssumed = (float) $budgets->sum('expense_assumed');
            $savingAssumed = (float) $budgets->sum('saving_assumed');
            if ($budgets->isEmpty()) {
                $savingAssumed = 0;
            }
        } else {
            $budget = Budget::where('month', $currentMonth)->where('year', $currentYear)->first();
            $expenses = Expense::whereNull('deleted_at')
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->orderBy('spend_date', 'desc')
                ->get();

            $salary = (float) ($budget?->amount ?? 0);
            $expenseAssumed = (float) ($budget?->expense_assumed ?? 0);
            $savingAssumed = (float) ($budget?->saving_assumed ?? max($salary - $expenseAssumed, 0));
        }

        $expenseReal = (float) $expenses->sum('amount');
        $savingReal = max($salary - $expenseReal, 0);
        $limitExceeded = $expenseReal > $salary;
        $expenseFromSavings = max($expenseReal - $expenseAssumed, 0);
        $remainingBalance = max($salary - $expenseReal, 0);

        $usage = $expenses->groupBy('category')->map(function ($items, $cat) {
            $records = $items->sortByDesc('spend_date')->map(function (Expense $e) {
                return [
                    'id' => $e->id,
                    'amount' => (string) $e->amount,
                    'spend_date' => $e->spend_date ? $e->spend_date->toDateString() : null,
                    'category' => $e->category,
                ];
            })->values();
            return [
                'name' => $cat,
                'spend' => number_format((float) $items->sum('amount'), 2, '.', ''),
                'records' => $records,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'filters' => [
                'month' => $currentMonth,
                'year' => $currentYear,
                'is_ytd' => !$isFiltered,
                'start_month' => !$isFiltered ? 1 : $currentMonth,
            ],
            'data' => [
                'salary' => number_format($salary, 2, '.', ''),
                'expense' => [
                    'assumption' => number_format($expenseAssumed, 2, '.', ''),
                    'real' => number_format($expenseReal, 2, '.', ''),
                ],
                'saving' => [
                    'assumption' => number_format($savingAssumed, 2, '.', ''),
                    'real' => number_format($savingReal, 2, '.', ''),
                ],
                'limit_exceeded' => $limitExceeded,
                'expense_from_savings' => number_format($expenseFromSavings, 2, '.', ''),
                'remaining_balance' => number_format($remainingBalance, 2, '.', ''),
                'budget_usage' => $usage,
            ],
        ]);
    }
}
