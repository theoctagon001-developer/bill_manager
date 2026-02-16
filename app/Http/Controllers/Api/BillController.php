<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage     = (int) $request->get('per_page', 15);
        $billAccount = $request->query('bill_account');
        $monthsCount = (int) $request->query('months_count', 12);
        $category    = $request->query('category');
        $isPaid      = $request->query('is_paid');
        $monthParam  = $request->query('month');

        $query = Bill::query();
        if ($billAccount) {
            $query->where('bill_account', $billAccount);
        }

        if (! is_null($category)) {
            $query->where('category', $category);
        }

        if (! is_null($isPaid)) {
            $query->where('is_paid', (bool) $isPaid);
        }
        if ($monthParam) {
            try {
                $month = Carbon::createFromFormat('Y-m', $monthParam);
            } catch (\Exception $e) {
                $month = Carbon::createFromFormat('m-Y', $monthParam);
            }
            $startDate = $month->startOfMonth();
            $endDate   = $month->endOfMonth();
        } else {
            $startDate = now()->startOfMonth()->subMonths($monthsCount - 1);
            $endDate   = now()->endOfMonth();
        }

        $query->whereBetween('due_date', [$startDate, $endDate]);
        $bills = $query->orderBy('due_date', 'desc')->paginate($perPage);
        $groupedByAccount = collect($bills->items())->groupBy('bill_account');

        $response = $groupedByAccount->map(function ($accountBills, $account) use ($monthsCount, $monthParam) {
            $months = collect();
            if ($monthParam) {
                $monthName = Carbon::parse($monthParam)->format('F-Y');
                $months->put($monthName, collect());
            } else {
                for ($i = 0; $i < $monthsCount; $i++) {
                    $monthName = now()->subMonths($i)->format('F-Y');
                    $months->put($monthName, collect());
                }
            }
            $groupedBillsByMonth = $accountBills->groupBy(fn($bill) => $bill->due_date->format('F-Y'));
            $months = $months->map(function ($bills, $month) use ($groupedBillsByMonth) {
                if (isset($groupedBillsByMonth[$month])) {
                    return $groupedBillsByMonth[$month]
                        ->map(fn($bill) => $this->transformBill($bill))
                        ->values();
                }
                return collect();
            });

            $months = $months->map(function ($bills, $month) {
                return [
                    'month' => $month,
                    'bills' => $bills,
                ];
            })->values();
            $totalPaidRecords    = $accountBills->where('is_paid', true)->count();
            $totalClearedRecords = $accountBills->where('is_cleared', true)->count();

            return [
                'bill_account' => $account,
                'summary' => [
                    'total_bills'       => $accountBills->count(),
                    'total_amount'      => (string) $accountBills->sum('amount'),
                    'total_paid'        => (string) $accountBills->where('is_paid', true)->sum('amount'),
                    'total_cleared'     => (string) $accountBills->where('is_cleared', true)->sum('amount'),
                    'count_paid'        => $totalPaidRecords,
                    'count_cleared'     => $totalClearedRecords,
                ],
                'months' => $months,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'filters' => [
                'bill_account' => $billAccount,
                'months_count' => $monthsCount,
                'category'     => $category,
                'is_paid'      => $isPaid,
                'month'        => $monthParam,
            ],
            'data' => $response,
            'pagination' => [
                'current_page' => $bills->currentPage(),
                'per_page'     => $bills->perPage(),
                'total'        => $bills->total(),
                'last_page'    => $bills->lastPage(),
                'from'         => $bills->firstItem(),
                'to'           => $bills->lastItem(),
            ],
        ]);
    }

    /**
     * Transform a bill model into the API response shape.
     */
    protected function transformBill(Bill $bill): array
    {
        return [
            'id' => $bill->id,
            'amount' => (string) $bill->amount,
            'category' => $bill->category,
            'bill_account' => $bill->bill_account,
            'due_date' => $bill->due_date ? $bill->due_date->toDateString() : null,
            'is_paid' => (bool) $bill->is_paid,
            'is_cleared' => (bool) $bill->is_cleared,
            'image' => $bill->image
                ? asset('storage/' . ltrim($bill->image, '/'))
                : null,
        ];
    }

    /**
     * Display a listing of the resource with pagination, ordered by date desc.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);

        $bills = Bill::orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = collect($bills->items())
            ->map(fn(Bill $bill) => $this->transformBill($bill))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $bills->currentPage(),
                'per_page' => $bills->perPage(),
                'total' => $bills->total(),
                'last_page' => $bills->lastPage(),
                'from' => $bills->firstItem(),
                'to' => $bills->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'category' => 'required|string|max:255',
                'bill_account' => 'required|string|max:255',
                'due_date' => 'required|date',
                'is_paid' => 'sometimes|boolean',
                'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')
                    ->store('bills', 'public');
            }

            $bill = Bill::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Bill created successfully',
                'data' => $this->transformBill($bill),
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
                'message' => 'Failed to create bill',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource (search by id).
     */
    public function show(int $id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformBill($bill),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage (fields optional).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);

            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0',
                'category' => 'sometimes|string|max:255',
                'bill_account' => 'sometimes|string|max:255',
                'due_date' => 'sometimes|date',
                'is_paid' => 'sometimes|boolean',
                'is_cleared' => 'sometimes|boolean',
                'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
            if ($request->hasFile('image')) {
                if ($bill->image && Storage::disk('public')->exists($bill->image)) {
                    Storage::disk('public')->delete($bill->image);
                }
                $validated['image'] = $request->file('image')
                    ->store('bills', 'public');
            }
            if (! empty($validated)) {
                $bill->update($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bill updated successfully',
                'data' => $this->transformBill($bill->fresh()),
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
                'message' => 'Bill not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update bill',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft delete the specified bill.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);
            if ($bill->image && Storage::disk('public')->exists($bill->image)) {
                Storage::disk('public')->delete($bill->image);
            }
            $bill->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bill deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bill',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateImage(Request $request, int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        $validated = $request->validate([
            'image' => 'required|image|max:2048',
        ]);
        if ($bill->image && Storage::disk('public')->exists($bill->image)) {
            Storage::disk('public')->delete($bill->image);
        }
        $path = $request->file('image')->store('bills', 'public');
        $bill->image = $path;
        $bill->save();
        return response()->json([
            'success' => true,
            'message' => 'Bill image updated successfully',
            'image_url' => url('storage/' . $path),
        ]);
    }
    public function deleteImage(int $id): JsonResponse
    {
        $bill = Bill::findOrFail($id);
        if ($bill->image && Storage::disk('public')->exists($bill->image)) {
            Storage::disk('public')->delete($bill->image);
            $bill->image = null;
            $bill->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Bill image deleted successfully',
        ]);
    }
    public function downloadImage(int $id)
    {
        $bill = Bill::findOrFail($id);

        if (! $bill->image || ! Storage::disk('public')->exists($bill->image)) {
            abort(404, 'Image not found');
        }
        return Storage::disk('public')->download($bill->image);
    }
}
