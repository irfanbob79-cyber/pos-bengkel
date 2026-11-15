<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\ProfitLossService;
use App\Services\PurchaseService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProfitLossService $plService,
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Mobile Ringkasan Laporan Laba Rugi Real-Time.
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id;
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfMonth();

        $data = $this->plService->calculate($branchId, $startDate, $endDate);

        return $this->successResponse($data, 'Ringkasan Laba Rugi berhasil dihitung.');
    }

    /**
     * Daftar Purchase Order (PO).
     */
    public function listPurchases(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;

        $purchases = Purchase::with(['supplier', 'branch', 'items.product'])
            ->where('branch_id', $branchId)
            ->latest()
            ->paginate(15);

        return $this->successResponse($purchases, 'Daftar PO berhasil diambil.');
    }

    /**
     * Buat PO baru dari Android.
     */
    public function createPurchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'payment_type' => 'required|in:cash,tempo',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
        ]);

        $validated['branch_id'] = $validated['branch_id'] ?? $request->user()->branch_id ?? 1;
        $validated['user_id'] = $request->user()->id;

        try {
            $purchase = $this->purchaseService->createPurchase($validated);

            return $this->successResponse($purchase, 'Purchase Order berhasil dibuat.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Konfirmasi penerimaan barang PO di gudang via HP Android.
     */
    public function receivePurchase(int $id, Request $request): JsonResponse
    {
        try {
            $purchase = $this->purchaseService->receivePurchase($id, $request->user()->id);

            return $this->successResponse($purchase, 'Penerimaan barang PO berhasil dikonfirmasi. Stok dan HPP telah disesuaikan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
