<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Services\StockOpnameService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StockOpnameService $opnameService
    ) {}

    /**
     * Mobile Quick Scan Check:
     * Cek info sparepart, stok, lokasi rak, dan harga via kamera scanner HP Android.
     */
    public function quickCheck(Request $request): JsonResponse
    {
        $request->validate([
            'barcode' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;

        try {
            $data = $this->opnameService->quickScanCheck(
                branchId: (int) $branchId,
                barcodeOrSku: trim($request->input('barcode'))
            );

            return $this->successResponse($data, 'Data produk berhasil ditemukan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Mobile Stock Opname Scan:
     * Admin gudang mencatat hasil scan fisik dari HP Android.
     */
    public function recordOpnameScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opname_id' => 'required|integer|exists:stock_opnames,id',
            'barcode' => 'required|string',
            'qty' => 'nullable|numeric|min:0.01',
            'replace' => 'nullable|boolean',
        ]);

        try {
            $item = $this->opnameService->recordScan(
                opnameId: (int) $validated['opname_id'],
                barcodeOrSku: trim($validated['barcode']),
                qty: (float) ($validated['qty'] ?? 1),
                replace: (bool) ($validated['replace'] ?? false)
            );

            return $this->successResponse([
                'product_name' => $item->product->name,
                'scanned_qty' => (float) $item->scanned_qty,
                'system_qty' => (float) $item->system_qty,
                'difference_qty' => (float) $item->difference_qty,
            ], 'Scan berhasil dicatat.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Mobile Riwayat Kartu Stok
     */
    public function stockCard(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;

        $movements = StockMovement::with(['user', 'product'])
            ->where('branch_id', $branchId)
            ->where('product_id', $request->input('product_id'))
            ->latest()
            ->paginate(15);

        return $this->successResponse($movements, 'Riwayat kartu stok berhasil diambil.');
    }
}
