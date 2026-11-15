<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkOrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WorkOrderService $workOrderService
    ) {}

    /**
     * Cari kendaraan berdasarkan Plat Nomor (termasuk riwayat servis lintas cabang).
     */
    public function searchVehicle(Request $request): JsonResponse
    {
        $request->validate([
            'plate_number' => 'required|string',
        ]);

        $plate = strtoupper(preg_replace('/\s+/', ' ', trim($request->input('plate_number'))));

        try {
            $data = $this->workOrderService->getVehicleServiceHistory($plate);

            return $this->successResponse($data, 'Data kendaraan & riwayat servis ditemukan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Daftarkan kendaraan & pemilik baru dari HP Android.
     */
    public function registerVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'motor_model_id' => 'required|integer|exists:motor_models,id',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:30',
            'current_km' => 'nullable|integer|min:0',
        ]);

        $customer = Customer::create([
            'name' => $validated['customer_name'],
            'phone' => $validated['customer_phone'] ?? null,
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'motor_model_id' => $validated['motor_model_id'],
            'plate_number' => strtoupper(trim($validated['plate_number'])),
            'last_km' => $validated['current_km'] ?? 0,
        ]);

        return $this->successResponse(
            $vehicle->load(['customer', 'motorModel']),
            'Kendaraan baru berhasil didaftarkan.',
            201
        );
    }

    /**
     * Daftar Antrean PKB Cabang.
     */
    public function listWorkOrders(Request $request): JsonResponse
    {
        $branchId = $request->input('branch_id') ?? $request->user()->branch_id ?? 1;
        $status = $request->input('status');
        $mechanicId = $request->input('mechanic_id');

        $query = WorkOrder::with(['vehicle.motorModel', 'customer', 'mechanic', 'serviceAdvisor', 'items'])
            ->where('branch_id', $branchId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($mechanicId, fn ($q) => $q->where('mechanic_id', $mechanicId))
            ->latest();

        return $this->successResponse($query->paginate(20), 'Daftar PKB berhasil diambil.');
    }

    /**
     * Buat tiket PKB baru.
     */
    public function createWorkOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'mechanic_id' => 'nullable|integer|exists:users,id',
            'current_km' => 'nullable|integer|min:0',
            'complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
        ]);

        $branchId = $validated['branch_id'] ?? $request->user()->branch_id ?? 1;

        try {
            $wo = $this->workOrderService->createWorkOrder([
                'branch_id' => $branchId,
                'vehicle_id' => $validated['vehicle_id'],
                'service_advisor_id' => $request->user()->id,
                'mechanic_id' => $validated['mechanic_id'] ?? null,
                'current_km' => $validated['current_km'] ?? 0,
                'complaints' => $validated['complaints'] ?? null,
                'diagnosis' => $validated['diagnosis'] ?? null,
            ]);

            return $this->successResponse($wo, 'Tiket PKB berhasil dibuat.', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Ambil detail PKB beserta item & kalkulasi total.
     */
    public function showWorkOrder(int $id): JsonResponse
    {
        $wo = WorkOrder::with([
            'vehicle.motorModel',
            'customer',
            'mechanic',
            'serviceAdvisor',
            'items.product',
            'items.service',
            'items.mechanic',
        ])->find($id);

        if (! $wo) {
            return $this->errorResponse('Tiket PKB tidak ditemukan.', 404);
        }

        $data = $wo->toArray();
        $data['total_cost'] = $wo->calculateTotal();

        return $this->successResponse($data, 'Detail PKB berhasil diambil.');
    }

    /**
     * Tambah item part/jasa ke PKB.
     */
    public function addItem(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:product,service',
            'item_id' => 'required|integer',
            'qty' => 'nullable|numeric|min:0.01',
            'custom_price' => 'nullable|numeric|min:0',
            'mechanic_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $item = $this->workOrderService->addItem(
                woId: $id,
                itemType: $validated['item_type'],
                itemId: (int) $validated['item_id'],
                qty: (float) ($validated['qty'] ?? 1),
                mechanicId: $validated['mechanic_id'] ?? null,
                customPrice: isset($validated['custom_price']) ? (float) $validated['custom_price'] : null,
                notes: $validated['notes'] ?? null
            );

            return $this->successResponse($item, 'Item berhasil ditambahkan ke PKB.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update status PKB (misal mekanik mulai atau selesai pengerjaan).
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,ready_for_payment,cancelled',
        ]);

        try {
            $status = $validated['status'];
            if ($status === 'in_progress') {
                $wo = $this->workOrderService->startWorkOrder($id, $request->user()->id);
            } elseif ($status === 'ready_for_payment') {
                $wo = $this->workOrderService->finishWorkOrder($id);
            } elseif ($status === 'cancelled') {
                $wo = $this->workOrderService->cancelWorkOrder($id);
            } else {
                throw new \Exception('Status tidak dikenali.');
            }

            return $this->successResponse($wo, 'Status PKB berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Riwayat Komisi Mekanik yang sedang login.
     */
    public function myCommissions(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = WorkOrderItem::with(['workOrder.vehicle', 'service'])
            ->where('mechanic_id', $user->id)
            ->whereHas('workOrder', fn ($q) => $q->whereIn('status', [WorkOrderStatus::ReadyForPayment, WorkOrderStatus::Completed]))
            ->latest()
            ->paginate(20);

        $totalEarned = WorkOrderItem::where('mechanic_id', $user->id)
            ->whereHas('workOrder', fn ($q) => $q->whereIn('status', [WorkOrderStatus::ReadyForPayment, WorkOrderStatus::Completed]))
            ->sum('commission_amount');

        return $this->successResponse([
            'total_commission' => (float) $totalEarned,
            'history' => $items,
        ], 'Data komisi mekanik berhasil diambil.');
    }
}
