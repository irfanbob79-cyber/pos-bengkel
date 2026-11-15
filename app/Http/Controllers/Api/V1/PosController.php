<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SaleStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\WorkOrder;
use App\Services\PosCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PosCheckoutService $posService
    ) {}

    /**
     * Cek status shift kasir saat ini.
     */
    public function registerStatus(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;
        $register = $this->posService->getActiveRegister($branchId, $request->user()->id);

        return $this->successResponse([
            'is_open' => $register !== null,
            'register' => $register,
        ], 'Status shift kasir.');
    }

    /**
     * Buka Shift Kasir.
     */
    public function openRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $branchId = $validated['branch_id'] ?? $request->user()->branch_id ?? 1;

        try {
            $register = $this->posService->openRegister(
                branchId: (int) $branchId,
                userId: $request->user()->id,
                openingBalance: (float) $validated['opening_balance'],
                notes: $validated['notes'] ?? null
            );

            return $this->successResponse($register, 'Shift kasir berhasil dibuka.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Tutup Shift Kasir.
     */
    public function closeRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_register_id' => 'required|integer|exists:cash_registers,id',
            'physical_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $register = $this->posService->closeRegister(
                registerId: (int) $validated['cash_register_id'],
                physicalBalance: (float) $validated['physical_balance'],
                notes: $validated['notes'] ?? null
            );

            return $this->successResponse($register, 'Shift kasir berhasil ditutup dan direkonsiliasi.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Checkout POS (Lunas) dengan dukungan Split Payment & Snapshot HPP.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'cash_register_id' => 'nullable|integer|exists:cash_registers,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'work_order_id' => 'nullable|integer|exists:work_orders,id',
            'price_tier_id' => 'required|integer|exists:price_tiers,id',
            'total_gross' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:product,service',
            'items.*.item_id' => 'required|integer',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.mechanic_id' => 'nullable|integer',
            'items.*.commission_amount' => 'nullable|numeric',
        ]);

        $validated['branch_id'] = $validated['branch_id'] ?? $request->user()->branch_id ?? 1;
        $validated['user_id'] = $request->user()->id;

        try {
            $sale = $this->posService->checkout($validated);

            return $this->successResponse($sale, 'Transaksi POS berhasil diselesaikan.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Daftar Tagihan yang di-Hold (Suspended Bills).
     */
    public function listSuspendedBills(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;

        $bills = Sale::with(['customer', 'items.product', 'items.service'])
            ->where('branch_id', $branchId)
            ->where('status', SaleStatus::Suspended)
            ->latest()
            ->get();

        return $this->successResponse($bills, 'Daftar transaksi tertunda.');
    }

    /**
     * Daftar PKB Servis yang Siap Bayar.
     */
    public function listReadyWorkOrders(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;

        $orders = WorkOrder::with(['customer', 'vehicle.motorModel', 'items.product', 'items.service'])
            ->where('branch_id', $branchId)
            ->where('status', WorkOrderStatus::ReadyForPayment)
            ->latest()
            ->get();

        return $this->successResponse($orders, 'Daftar PKB siap bayar.');
    }
}
