<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $start = $request->query('start_month', \Carbon\Carbon::now()->format('Y') . '-01');
        $end = $request->query('end_month', \Carbon\Carbon::now()->format('Y-m'));
        $range = null;
        if ($start && $end) {
            $startParts = explode('-', $start);
            $endParts = explode('-', $end);
            if (count($startParts) === 2 && count($endParts) === 2) {
                $range = [
                    'start_month' => (int) $startParts[1],
                    'start_year' => (int) $startParts[0],
                    'end_month' => (int) $endParts[1],
                    'end_year' => (int) $endParts[0],
                ];
            }
        }

        $budgetsQuery = Budget::query();
        $expensesQuery = Expense::query()->whereNull('deleted_at');

        if ($range) {
            $budgetsQuery->where(function ($q) use ($range) {
                $q->where(function ($q2) use ($range) {
                    $q2->where('year', $range['start_year'])
                        ->where('month', '>=', $range['start_month']);
                })->orWhere(function ($q2) use ($range) {
                    $q2->where('year', $range['end_year'])
                        ->where('month', '<=', $range['end_month']);
                })->orWhereBetween('year', [$range['start_year'], $range['end_year']]);
            });
            $expensesQuery->where(function ($q) use ($range) {
                $q->where(function ($q2) use ($range) {
                    $q2->where('year', $range['start_year'])
                        ->where('month', '>=', $range['start_month']);
                })->orWhere(function ($q2) use ($range) {
                    $q2->where('year', $range['end_year'])
                        ->where('month', '<=', $range['end_month']);
                })->orWhereBetween('year', [$range['start_year'], $range['end_year']]);
            });
        }

        $budgets = $budgetsQuery->get();
        $expenses = $expensesQuery->get();

        $totalAmount = (float) $budgets->sum('amount');
        $totalAssumedExpense = (float) $budgets->sum('expense_assumed');
        $totalAssumedSaving = (float) $budgets->sum('saving_assumed');
        $totalActualExpense = (float) $expenses->sum('amount');
        $totalActualSaving = max($totalAmount - $totalActualExpense, 0);

        $response = [
            'success' => true,
            'global' => [
                'Total_Amount' => (string) $totalAmount,
                'Total_Expense_Assumed' => (string) $totalAssumedExpense,
                'Total_Saving_Assumed' => (string) $totalAssumedSaving,
                'Total_Actual_Saving' => (string) $totalActualSaving,
            ],
        ];

        if ($range) {
            $details = [];
            $months = $budgets->map(fn($b) => [$b->year, $b->month])->unique()->values();
            foreach ($months as $ym) {
                $y = $ym[0];
                $m = $ym[1];
                $b = $budgets->first(fn($bb) => $bb->year === $y && $bb->month === $m);
                $exp = $expenses->where('year', $y)->where('month', $m);
                $actualExpense = (float) $exp->sum('amount');
                $details[] = [
                    'year' => $y,
                    'month' => $m,
                    'Total_Amount' => (string) ($b?->amount ?? 0),
                    'Total_Expense_Assumed' => (string) ($b?->expense_assumed ?? 0),
                    'Total_Saving_Assumed' => (string) ($b?->saving_assumed ?? 0),
                    'Total_Actual_Saving' => (string) max((float) ($b?->amount ?? 0) - $actualExpense, 0),
                ];
            }
            $response['range'] = [
                'start_month' => $start,
                'end_month' => $end,
            ];
            $response['details'] = $details;
        }

        return response()->json($response);
    }
}
