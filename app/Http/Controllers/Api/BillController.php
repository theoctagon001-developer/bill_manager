<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BillController extends Controller
{
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
            // readable date (YYYY-MM-DD)
            'due_date' => $bill->due_date ? $bill->due_date->toDateString() : null,
            'is_paid' => (bool) $bill->is_paid,
        ];
    }

    /**
     * Display a listing of the resource with pagination, ordered by date desc.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        
        $bills = Bill::orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = collect($bills->items())
            ->map(fn (Bill $bill) => $this->transformBill($bill))
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
            ]);

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
            ]);

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
}
