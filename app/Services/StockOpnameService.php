<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\StockOpnameScope;
use App\Enums\StockOpnameStatus;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Exception;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Buka sesi Stock Opname baru dengan snapshot stok sistem.
     */
    public function createSession(
        int $branchId,
        int $userId,
        StockOpnameScope $scopeType = StockOpnameScope::All,
        ?string $scopeValue = null,
        ?string $notes = null
    ): StockOpname {
        return DB::transaction(function () use ($branchId, $userId, $scopeType, $scopeValue, $notes) {
            $datePrefix = date('Ymd');
            $randomSuffix = strtoupper(substr(uniqid(), -4));
            $opnameNumber = "OPN-{$datePrefix}-{$randomSuffix}";

            $opname = StockOpname::create([
                'opname_number' => $opnameNumber,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'scope_type' => $scopeType,
                'scope_value' => $scopeValue,
                'status' => StockOpnameStatus::InProgress,
                'notes' => $notes,
            ]);

            // Query produk sesuai cakupan (Scope)
            $query = Product::where('is_active', true);

            if ($scopeType === StockOpnameScope::Category && $scopeValue) {
                $query->where('category_id', $scopeValue);
            }

            $products = $query->get();

            foreach ($products as $product) {
                $stock = BranchStock::where('branch_id', $branchId)
                    ->where('product_id', $product->id)
                    ->first();

                // Jika filter berdasarkan Lokasi Rak
                if ($scopeType === StockOpnameScope::BinLocation && $scopeValue) {
                    if (! $stock || $stock->bin_location !== $scopeValue) {
                        continue;
                    }
                }

                $systemQty = $stock ? (float) $stock->qty : 0.0;

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $product->id,
                    'system_qty' => $systemQty,
                    'scanned_qty' => 0,
                    'difference_qty' => -$systemQty,
                    'cost_snapshot' => $product->moving_avg_cost,
                    'is_scanned' => false,
                ]);
            }

            return $opname;
        });
    }

    /**
     * Catat hasil scan barcode / SKU pada sesi opname yang sedang berjalan.
     */
    public function recordScan(
        int $opnameId,
        string $barcodeOrSku,
        float $qty = 1,
        bool $replace = false
    ): StockOpnameItem {
        return DB::transaction(function () use ($opnameId, $barcodeOrSku, $qty, $replace) {
            $opname = StockOpname::where('id', $opnameId)->lockForUpdate()->first();

            if (! $opname || $opname->status !== StockOpnameStatus::InProgress) {
                throw new Exception("Sesi stock opname tidak aktif atau sudah ditutup.");
            }

            $product = Product::where('barcode', $barcodeOrSku)
                ->orWhere('sku', $barcodeOrSku)
                ->first();

            if (! $product) {
                throw new Exception("Sparepart dengan Barcode/SKU '{$barcodeOrSku}' tidak ditemukan dalam katalog.");
            }

            $item = StockOpnameItem::where('stock_opname_id', $opnameId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                // Jika barang ini belum masuk daftar snapshot (misal produk baru), buatkan barisnya
                $currentStock = BranchStock::where('branch_id', $opname->branch_id)
                    ->where('product_id', $product->id)
                    ->value('qty') ?? 0;

                $item = StockOpnameItem::create([
                    'stock_opname_id' => $opnameId,
                    'product_id' => $product->id,
                    'system_qty' => $currentStock,
                    'scanned_qty' => 0,
                    'difference_qty' => -$currentStock,
                    'cost_snapshot' => $product->moving_avg_cost,
                    'is_scanned' => false,
                ]);
            }

            $newScannedQty = $replace ? $qty : ($item->scanned_qty + $qty);
            $newDifference = $newScannedQty - $item->system_qty;

            $item->update([
                'scanned_qty' => $newScannedQty,
                'difference_qty' => $newDifference,
                'is_scanned' => true,
            ]);

            return $item->load('product');
        });
    }

    /**
     * Quick Scan Check: Cek info sparepart, stok cabang saat ini, lokasi rak & harga tanpa mengubah data.
     */
    public function quickScanCheck(int $branchId, string $barcodeOrSku): array
    {
        $product = Product::with(['category', 'brand', 'prices.priceTier'])
            ->where('barcode', $barcodeOrSku)
            ->orWhere('sku', $barcodeOrSku)
            ->first();

        if (! $product) {
            throw new Exception("Sparepart dengan kode '{$barcodeOrSku}' tidak ditemukan.");
        }

        $stock = BranchStock::where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->first();

        $prices = [];
        foreach ($product->prices as $p) {
            $prices[] = [
                'tier_code' => $p->priceTier->code,
                'tier_name' => $p->priceTier->name,
                'price' => (float) $p->price,
                'min_qty' => $p->min_qty,
            ];
        }

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'quality_type' => $product->quality_type->label(),
            'category' => $product->category?->name ?? '-',
            'brand' => $product->brand?->name ?? '-',
            'bin_location' => $stock?->bin_location ?? 'Belum Diatur',
            'current_stock' => $stock ? (float) $stock->qty : 0.0,
            'min_stock' => $stock ? (float) $stock->min_stock : 0.0,
            'is_low_stock' => $stock ? $stock->isLowStock() : true,
            'prices' => $prices,
        ];
    }

    /**
     * Approval Sesi Opname oleh Branch Manager / Owner:
     * Menyesuaikan stok fisik riil di sistem dan mencatat selisih ke Kartu Stok.
     */
    public function approveSession(int $opnameId, int $approverId): StockOpname
    {
        return DB::transaction(function () use ($opnameId, $approverId) {
            $opname = StockOpname::with('items')->where('id', $opnameId)->lockForUpdate()->first();

            if (! $opname || $opname->status !== StockOpnameStatus::InProgress) {
                throw new Exception("Hanya sesi opname berstatus In-Progress yang dapat disetujui.");
            }

            foreach ($opname->items as $item) {
                // Sesuaikan stok di branch_stocks
                $this->stockService->adjustStock(
                    branchId: $opname->branch_id,
                    productId: $item->product_id,
                    actualQty: (float) $item->scanned_qty,
                    type: StockMovementType::OpnameAdjustment,
                    referenceType: StockOpname::class,
                    referenceId: $opname->id,
                    userId: $approverId,
                    notes: "Penyesuaian hasil opname #{$opname->opname_number}. Sistem: {$item->system_qty}, Fisik: {$item->scanned_qty}, Selisih: {$item->difference_qty}"
                );
            }

            $opname->update([
                'status' => StockOpnameStatus::Completed,
                'approved_by_id' => $approverId,
                'completed_at' => now(),
            ]);

            return $opname;
        });
    }
}
