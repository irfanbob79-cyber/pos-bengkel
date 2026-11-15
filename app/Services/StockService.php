<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Tambah stok fisik di cabang tertentu dengan concurrency lock & pencatatan kartu stok.
     */
    public function increaseStock(
        int $branchId,
        int $productId,
        float $qty,
        StockMovementType $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): BranchStock {
        if ($qty <= 0) {
            throw new Exception("Kuantitas penambahan stok harus lebih besar dari 0.");
        }

        return DB::transaction(function () use (
            $branchId,
            $productId,
            $qty,
            $type,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        ) {
            $stock = BranchStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = BranchStock::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty' => 0,
                    'min_stock' => 5,
                ]);
            }

            $balanceAfter = $stock->qty + $qty;
            $stock->update(['qty' => $balanceAfter]);

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'qty_change' => $qty,
                'balance_after' => $balanceAfter,
                'notes' => $notes,
            ]);

            return $stock;
        });
    }

    /**
     * Kurangi stok fisik di cabang tertentu dengan concurrency lock & proteksi stok minus.
     */
    public function decreaseStock(
        int $branchId,
        int $productId,
        float $qty,
        StockMovementType $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): BranchStock {
        if ($qty <= 0) {
            throw new Exception("Kuantitas pengurangan stok harus lebih besar dari 0.");
        }

        return DB::transaction(function () use (
            $branchId,
            $productId,
            $qty,
            $type,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        ) {
            $stock = BranchStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $currentQty = $stock ? (float) $stock->qty : 0.0;

            if ($currentQty < $qty) {
                $product = Product::find($productId);
                $productName = $product ? $product->name : "ID #{$productId}";
                throw new Exception("Stok tidak mencukupi untuk {$productName}. Sisa stok: {$currentQty}, diminta: {$qty}.");
            }

            $balanceAfter = $currentQty - $qty;
            $stock->update(['qty' => $balanceAfter]);

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'qty_change' => -$qty,
                'balance_after' => $balanceAfter,
                'notes' => $notes,
            ]);

            return $stock;
        });
    }

    /**
     * Penyesuaian stok (biasanya dari hasil Stock Opname atau Koreksi Saldo Awal).
     */
    public function adjustStock(
        int $branchId,
        int $productId,
        float $actualQty,
        StockMovementType $type = StockMovementType::OpnameAdjustment,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): BranchStock {
        return DB::transaction(function () use (
            $branchId,
            $productId,
            $actualQty,
            $type,
            $referenceType,
            $referenceId,
            $userId,
            $notes
        ) {
            $stock = BranchStock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $previousQty = $stock ? (float) $stock->qty : 0.0;
            $difference = $actualQty - $previousQty;

            if (! $stock) {
                $stock = BranchStock::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'qty' => $actualQty,
                    'min_stock' => 5,
                ]);
            } else {
                $stock->update(['qty' => $actualQty]);
            }

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'qty_change' => $difference,
                'balance_after' => $actualQty,
                'notes' => $notes ?? "Penyesuaian stok dari {$previousQty} menjadi {$actualQty} (Selisih: {$difference})",
            ]);

            return $stock;
        });
    }

    /**
     * Kirim barang transfer antar cabang (Status -> In-Transit).
     * Mengurangi stok cabang asal secara atomik.
     */
    public function dispatchTransfer(StockTransfer $transfer, ?int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            $transfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if ($transfer->status !== StockTransferStatus::Draft) {
                throw new Exception("Hanya transfer berstatus Draft yang dapat dikirim.");
            }

            foreach ($transfer->items as $item) {
                $this->decreaseStock(
                    branchId: $transfer->from_branch_id,
                    productId: $item->product_id,
                    qty: (float) $item->qty_sent,
                    type: StockMovementType::TransferOut,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    userId: $userId ?? $transfer->user_id,
                    notes: "Mutasi keluar ke cabang tujuan #{$transfer->to_branch_id} (No: {$transfer->transfer_number})"
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::InTransit,
                'sent_at' => now(),
            ]);
        });
    }

    /**
     * Terima barang transfer di cabang tujuan (Status -> Completed).
     * Menambah stok cabang tujuan secara atomik.
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities, int $receivedByUserId): void
    {
        DB::transaction(function () use ($transfer, $receivedQuantities, $receivedByUserId) {
            $transfer = StockTransfer::where('id', $transfer->id)->lockForUpdate()->first();

            if ($transfer->status !== StockTransferStatus::InTransit) {
                throw new Exception("Hanya transfer berstatus In-Transit yang dapat diterima.");
            }

            foreach ($transfer->items as $item) {
                $qtyReceived = isset($receivedQuantities[$item->id])
                    ? (float) $receivedQuantities[$item->id]
                    : (float) $item->qty_sent;

                $item->update(['qty_received' => $qtyReceived]);

                $this->increaseStock(
                    branchId: $transfer->to_branch_id,
                    productId: $item->product_id,
                    qty: $qtyReceived,
                    type: StockMovementType::TransferIn,
                    referenceType: StockTransfer::class,
                    referenceId: $transfer->id,
                    userId: $receivedByUserId,
                    notes: "Penerimaan mutasi dari cabang #{$transfer->from_branch_id} (No: {$transfer->transfer_number})"
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::Completed,
                'received_by_id' => $receivedByUserId,
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Perbarui HPP Produk menggunakan metode Weighted Moving Average.
     */
    public function updateMovingAverageCost(int $productId, float $purchasedQty, float $unitCost): float
    {
        return DB::transaction(function () use ($productId, $purchasedQty, $unitCost) {
            $product = Product::where('id', $productId)->lockForUpdate()->first();
            if (! $product) {
                return 0.0;
            }

            $currentStock = (float) BranchStock::where('product_id', $productId)->sum('qty');
            $currentHpp = (float) $product->moving_avg_cost;

            $totalQty = $currentStock + $purchasedQty;
            if ($totalQty <= 0) {
                $newHpp = $unitCost;
            } else {
                $newHpp = (($currentStock * $currentHpp) + ($purchasedQty * $unitCost)) / $totalQty;
            }

            $newHpp = round($newHpp, 2);
            $product->update(['moving_avg_cost' => $newHpp]);

            return $newHpp;
        });
    }
}
