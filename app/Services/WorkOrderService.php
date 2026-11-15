<?php

namespace App\Services;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    /**
     * Daftarkan tiket servis baru (PKB) dengan status Queued.
     */
    public function createWorkOrder(array $data): WorkOrder
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::with('customer')->findOrFail($data['vehicle_id']);

            $datePrefix = date('Ymd');
            $randomSuffix = strtoupper(substr(uniqid(), -4));
            $woNumber = "WO-{$datePrefix}-{$randomSuffix}";

            $customerId = $data['customer_id'] ?? $vehicle->customer_id;
            $currentKm = (int) ($data['current_km'] ?? $vehicle->last_km);

            $workOrder = WorkOrder::create([
                'wo_number' => $woNumber,
                'branch_id' => $data['branch_id'],
                'vehicle_id' => $vehicle->id,
                'customer_id' => $customerId,
                'service_advisor_id' => $data['service_advisor_id'] ?? null,
                'mechanic_id' => $data['mechanic_id'] ?? null,
                'current_km' => $currentKm,
                'complaints' => $data['complaints'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'status' => WorkOrderStatus::Queued,
            ]);

            // Perbarui KM terakhir pada database kendaraan
            if ($currentKm > $vehicle->last_km) {
                $vehicle->update(['last_km' => $currentKm]);
            }

            return $workOrder->load(['vehicle.motorModel', 'customer', 'mechanic', 'serviceAdvisor']);
        });
    }

    /**
     * Mulai pengerjaan motor oleh mekanik (Status -> In-Progress).
     */
    public function startWorkOrder(int $woId, ?int $mechanicId = null): WorkOrder
    {
        return DB::transaction(function () use ($woId, $mechanicId) {
            $workOrder = WorkOrder::where('id', $woId)->lockForUpdate()->firstOrFail();

            if ($workOrder->status !== WorkOrderStatus::Queued) {
                throw new Exception("Hanya PKB berstatus Antrean (Queued) yang dapat dimulai.");
            }

            $updateData = ['status' => WorkOrderStatus::InProgress];
            if ($mechanicId) {
                $updateData['mechanic_id'] = $mechanicId;
            }

            $workOrder->update($updateData);

            return $workOrder;
        });
    }

    /**
     * Tambahkan item sparepart fisik atau jasa servis ke dalam PKB.
     */
    public function addItem(
        int $woId,
        string $itemType, // 'product' atau 'service'
        int $itemId,
        float $qty = 1,
        ?int $mechanicId = null,
        ?float $customPrice = null,
        ?string $notes = null
    ): WorkOrderItem {
        return DB::transaction(function () use ($woId, $itemType, $itemId, $qty, $mechanicId, $customPrice, $notes) {
            $workOrder = WorkOrder::with('customer')->where('id', $woId)->lockForUpdate()->firstOrFail();

            if (in_array($workOrder->status, [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])) {
                throw new Exception("Tidak dapat menambah item pada PKB yang sudah selesai atau dibatalkan.");
            }

            $assignedMechanicId = $mechanicId ?? $workOrder->mechanic_id;
            $unitPrice = 0.0;
            $commissionAmount = 0.0;
            $productId = null;
            $serviceId = null;

            if ($itemType === 'service') {
                $service = Service::findOrFail($itemId);
                $serviceId = $service->id;
                $unitPrice = $customPrice ?? (float) $service->default_price;
                $commissionAmount = $service->calculateCommission($unitPrice) * $qty;
            } elseif ($itemType === 'product') {
                $product = Product::findOrFail($itemId);
                $productId = $product->id;
                $priceTierId = $workOrder->customer?->price_tier_id;
                $unitPrice = $customPrice ?? $product->getPriceForTier($priceTierId, $qty);
                $commissionAmount = 0.0;
            } else {
                throw new Exception("Tipe item tidak valid: {$itemType}. Pilih 'product' atau 'service'.");
            }

            $subtotal = $unitPrice * $qty;

            return WorkOrderItem::create([
                'work_order_id' => $workOrder->id,
                'item_type' => $itemType,
                'product_id' => $productId,
                'service_id' => $serviceId,
                'mechanic_id' => $assignedMechanicId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'commission_amount' => $commissionAmount,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Hapus item dari PKB.
     */
    public function removeItem(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $item = WorkOrderItem::with('workOrder')->findOrFail($itemId);

            if (in_array($item->workOrder->status, [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled])) {
                throw new Exception("Tidak dapat menghapus item dari PKB yang sudah selesai.");
            }

            $item->delete();
        });
    }

    /**
     * Tandai pengerjaan selesai oleh mekanik (Status -> ReadyForPayment).
     * PKB akan otomatis siap ditarik kasir di Web POS.
     */
    public function finishWorkOrder(int $woId): WorkOrder
    {
        return DB::transaction(function () use ($woId) {
            $workOrder = WorkOrder::with(['vehicle', 'items'])->where('id', $woId)->lockForUpdate()->firstOrFail();

            if ($workOrder->status !== WorkOrderStatus::InProgress && $workOrder->status !== WorkOrderStatus::Queued) {
                throw new Exception("Hanya PKB berstatus pengerjaan (In-Progress) yang dapat ditandai selesai.");
            }

            $workOrder->update([
                'status' => WorkOrderStatus::ReadyForPayment,
                'completed_at' => now(),
            ]);

            // Set estimasi jadwal servis / ganti oli berkala berikutnya (misal 60 hari kemudian)
            if ($workOrder->vehicle) {
                $workOrder->vehicle->update([
                    'next_service_date' => Carbon::now()->addDays(60),
                ]);
            }

            return $workOrder;
        });
    }

    /**
     * Batalkan PKB.
     */
    public function cancelWorkOrder(int $woId): WorkOrder
    {
        return DB::transaction(function () use ($woId) {
            $workOrder = WorkOrder::where('id', $woId)->lockForUpdate()->firstOrFail();

            if ($workOrder->status === WorkOrderStatus::Completed) {
                throw new Exception("PKB yang sudah lunas tidak dapat dibatalkan melalui servis. Harus melalui Void Kasir.");
            }

            $workOrder->update(['status' => WorkOrderStatus::Cancelled]);

            return $workOrder;
        });
    }

    /**
     * Riwayat Servis Lengkap Lintas Cabang berdasarkan Plat Nomor Motor.
     */
    public function getVehicleServiceHistory(string $plateNumber): array
    {
        $vehicle = Vehicle::with(['customer', 'motorModel'])->where('plate_number', $plateNumber)->first();

        if (! $vehicle) {
            throw new Exception("Kendaraan dengan plat nomor '{$plateNumber}' tidak terdaftar.");
        }

        $history = WorkOrder::with(['branch', 'mechanic', 'serviceAdvisor', 'items.product', 'items.service'])
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', [WorkOrderStatus::ReadyForPayment, WorkOrderStatus::Completed])
            ->latest()
            ->get();

        return [
            'vehicle' => $vehicle,
            'history' => $history,
        ];
    }

    /**
     * Buat URL Pengingat Servis WhatsApp langsung ke nomor pelanggan.
     */
    public function generateWhatsAppReminderUrl(Vehicle $vehicle): ?string
    {
        $phone = $vehicle->customer?->phone;
        if (! $phone) {
            return null;
        }

        // Normalisasi nomor ke format 62xxx
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $customerName = $vehicle->customer->name ?? 'Pelanggan Setia';
        $motorName = $vehicle->motorModel->name ?? 'Motor Anda';
        $plate = $vehicle->plate_number;
        $lastKm = number_format($vehicle->last_km, 0, ',', '.');

        $message = "Halo Kak {$customerName},\n\nKami dari Bengkel Motor menginfokan bahwa kendaraan Anda {$motorName} ({$plate}) sudah waktunya melakukan servis berkala / ganti oli (KM terakhir tercatat: {$lastKm} KM).\n\nYuk rawat motor Anda agar performa tetap prima dan aman di jalan. Kami tunggu kedatangannya ya Kak! 🙏🛵";

        return "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);
    }
}
