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
    protected function transformBill(Bill $bill): array
    {
        $imageUrl = null;
        if ($bill->image) {
            $targetPath = 'bills/' . $bill->id . '.png';
            $disk = Storage::disk('public');

            if (!$disk->exists($targetPath)) {
                $raw = $bill->image;
                $decoded = null;

                if (is_string($raw)) {
                    if (str_contains($raw, ';base64,')) {
                        $parts = explode(';base64,', $raw);
                        $raw = end($parts);
                    }
                    if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $raw)) {
                        $decoded = base64_decode($raw, true);
                    } else {
                        $decoded = $raw;
                    }
                }

                if ($decoded) {
                    if (!$disk->exists('bills')) {
                        $disk->makeDirectory('bills');
                    }
                    $disk->put($targetPath, $decoded);
                }
            }

            if ($disk->exists($targetPath)) {
                $imageUrl = asset('storage/' . $targetPath);
            }
        }

        return [
            'id' => $bill->id,
            'amount' => (string) $bill->amount,
            'category' => $bill->category,
            'bill_account' => $bill->bill_account,
            'due_date' => $bill->due_date ? $bill->due_date->toDateString() : null,
            'is_paid' => (bool) $bill->is_paid,
            'is_cleared' => (bool) $bill->is_cleared,
            'image' => $imageUrl,
        ];
    }
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
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'category' => 'required|string|max:255',
                'bill_account' => 'required|string|max:255',
                'due_date' => 'required|date',
                'is_paid' => 'sometimes|boolean',
                'image' => 'sometimes',
            ]);
            if ($request->hasFile('image')) {
                $content = file_get_contents($request->file('image')->getRealPath());
                $validated['image'] = base64_encode($content);
            } elseif ($request->filled('image')) {
                $img = $request->input('image');
                if (is_string($img)) {
                    // Extract base64 if it's a data URI
                    if (str_contains($img, ';base64,')) {
                        $parts = explode(';base64,', $img);
                        $img = end($parts);
                    }
                    $validated['image'] = $img;
                }
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
    public function show($id): JsonResponse
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
    public function update(Request $request, $id): JsonResponse
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
                'image' => 'sometimes',
            ]);
            if ($request->hasFile('image')) {
                $content = file_get_contents($request->file('image')->getRealPath());
                $validated['image'] = base64_encode($content);
            } elseif ($request->filled('image')) {
                $img = $request->input('image');
                if (is_string($img)) {
                    if (str_contains($img, ';base64,')) {
                        $parts = explode(';base64,', $img);
                        $img = end($parts);
                    }
                    $validated['image'] = $img;
                }
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
    public function destroy($id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);
            if ($bill->image && Storage::disk('public')->exists($bill->image)) {
                Storage::disk('public')->delete($bill->image);
            }
            $bill->forceDelete();

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
    public function billsDashboard(Request $request): JsonResponse
    {
        try {
            $monthParam = $request->query('month');
            $yearParam  = $request->query('year');

            if ($monthParam && $yearParam) {
                $month = (int) $monthParam;
                $year  = (int) $yearParam;
            } else {
                $month = (int) now()->month;
                $year  = (int) now()->year;
            }

            $bills = Bill::whereYear('due_date', $year)
                ->whereMonth('due_date', $month)
                ->orderBy('due_date', 'desc')
                ->get();

            $totalAmount = (float) $bills->sum('amount');
            $totalPaidAmount = (float) $bills->where('is_paid', true)->sum('amount');
            $totalBills = $bills->count();
            $totalPaidBills = $bills->where('is_paid', true)->count();
            $unpaidCount = $bills->where('is_paid', false)->count();

            $items = $bills->map(fn(Bill $bill) => $this->transformBill($bill))->values();

            return response()->json([
                'success' => true,
                'filters' => [
                    'month' => $month,
                    'year' => $year,
                ],
                'summary' => [
                    'total_amount_to_be_paid' => number_format($totalAmount, 2, '.', ''),
                    'total_paid' => number_format($totalPaidAmount, 2, '.', ''),
                    'total_bills' => $totalBills,
                    'total_paid_bills' => $totalPaidBills,
                    'unpaid_count' => $unpaidCount,
                ],
                'data' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateImage(Request $request, $id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);
            $request->validate([
                'image' => 'required',
            ]);

            if ($request->hasFile('image')) {
                $content = file_get_contents($request->file('image')->getRealPath());
                $bill->image = base64_encode($content);
            } else {
                $img = $request->input('image');
                if (is_string($img)) {
                    if (str_contains($img, ';base64,')) {
                        $parts = explode(';base64,', $img);
                        $img = end($parts);
                    }
                    $bill->image = $img;
                }
            }

            $bill->save();
            $targetPath = 'bills/' . $bill->id . '.png';
            if (Storage::disk('public')->exists($targetPath)) {
                Storage::disk('public')->delete($targetPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bill image updated successfully',
                'data' => $this->transformBill($bill->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteImage($id): JsonResponse
    {
        try {
            $bill = Bill::findOrFail($id);
            $targetPath = 'bills/' . $bill->id . '.png';
            if (Storage::disk('public')->exists($targetPath)) {
                Storage::disk('public')->delete($targetPath);
            }
            $bill->image = null;
            $bill->save();
            return response()->json([
                'success' => true,
                'message' => 'Bill image deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function downloadImage($id)
    {
        $bill = Bill::findOrFail($id);

        $targetPath = 'bills/' . $bill->id . '.png';
        if (! Storage::disk('public')->exists($targetPath)) {
            if ($bill->image) {
                $raw = $bill->image;
                $decoded = null;
                if (is_string($raw)) {
                    if (str_contains($raw, ';base64,')) {
                        $raw = explode(';base64,', $raw, 2)[1];
                    }
                    $decoded = base64_decode($raw, true);
                }
                if ($decoded !== null) {
                    Storage::disk('public')->put($targetPath, $decoded);
                }
            }
        }
        if (! Storage::disk('public')->exists($targetPath)) {
            abort(404, 'Image not found');
        }
        return Storage::disk('public')->download($targetPath);
    }
    public function cleanImages(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date|before_or_equal:' . now()->subMonth()->toDateString(),
            ], [
                'date.before_or_equal' => 'You can only clean images for bills that are at least one month old.',
            ]);
            $date = $validated['date'];
            $disk = Storage::disk('public');
            $bills = Bill::where('created_at', '<=', $date)
                ->whereNotNull('image')
                ->get();
            $cleanedCount = 0;
            foreach ($bills as $bill) {
                $targetPath = 'bills/' . $bill->id . '.png';
                if ($disk->exists($targetPath)) {
                    $disk->delete($targetPath);
                }
                $bill->image = null;
                $bill->save();
                
                $cleanedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Cleaned images successfully',
                'cleaned_count' => $cleanedCount,
                'cleaned_before' => $date
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clean images',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
