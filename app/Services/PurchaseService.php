<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Buat Purchase Order (PO) baru.
     */
    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $datePrefix = date('Ymd');
            $randomSuffix = strtoupper(substr(uniqid(), -4));
            $purchaseNumber = "PO-{$datePrefix}-{$randomSuffix}";

            $paymentType = is_string($data['payment_type'])
                ? PaymentType::from($data['payment_type'])
                : $data['payment_type'];

            $totalAmount = 0.0;
            foreach ($data['items'] as $item) {
                $totalAmount += (float) $item['cost_price'] * (float) $item['qty'];
            }

            $purchase = Purchase::create([
                'purchase_number' => $purchaseNumber,
                'branch_id' => $data['branch_id'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => $data['user_id'],
                'payment_type' => $paymentType,
                'due_date' => $data['due_date'] ?? null,
                'status' => PurchaseStatus::Ordered,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $qty = (float) $item['qty'];
                $costPrice = (float) $item['cost_price'];
                $subtotal = $qty * $costPrice;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $qty,
                    'cost_price' => $costPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            return $purchase->load(['items.product', 'supplier', 'branch']);
        });
    }

    /**
     * Konfirmasi penerimaan barang di gudang:
     * - Tambah stok fisik cabang secara atomik
     * - Hitung ulang HPP Produk (Weighted Moving Average)
     * - Update status PO -> Received
     */
    public function receivePurchase(int $purchaseId, ?int $receivedByUserId = null): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $receivedByUserId) {
            $purchase = Purchase::with('items')->where('id', $purchaseId)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== PurchaseStatus::Ordered) {
                throw new Exception("Hanya PO berstatus Dipesan (Ordered) yang dapat dikonfirmasi penerimaannya.");
            }

            foreach ($purchase->items as $item) {
                // 1. Tambah stok cabang tujuan
                $this->stockService->increaseStock(
                    branchId: $purchase->branch_id,
                    productId: $item->product_id,
                    qty: (float) $item->qty,
                    type: StockMovementType::PurchaseIn,
                    referenceType: Purchase::class,
                    referenceId: $purchase->id,
                    userId: $receivedByUserId ?? $purchase->user_id,
                    notes: "Penerimaan PO #{$purchase->purchase_number} dari supplier"
                );

                // 2. Perbarui HPP Produk secara Moving Average
                $this->stockService->updateMovingAverageCost(
                    productId: $item->product_id,
                    purchasedQty: (float) $item->qty,
                    unitCost: (float) $item->cost_price
                );
            }

            $purchase->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            return $purchase;
        });
    }
}
