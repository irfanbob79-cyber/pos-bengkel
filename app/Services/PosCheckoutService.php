<?php

namespace App\Services;

use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Enums\WorkOrderStatus;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Service;
use App\Models\WorkOrder;
use Exception;
use Illuminate\Support\Facades\DB;

class PosCheckoutService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Buka Shift Kasir baru (Modal Awal).
     */
    public function openRegister(int $branchId, int $userId, float $openingBalance, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($branchId, $userId, $openingBalance, $notes) {
            $existing = CashRegister::where('branch_id', $branchId)
                ->where('user_id', $userId)
                ->where('status', CashRegisterStatus::Open)
                ->first();

            if ($existing) {
                return $existing;
            }

            return CashRegister::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'opening_balance' => $openingBalance,
                'opened_at' => now(),
                'status' => CashRegisterStatus::Open,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Tutup Shift Kasir (Rekonsiliasi Uang Fisik vs Sistem).
     */
    public function closeRegister(int $registerId, float $physicalBalance, ?string $notes = null): CashRegister
    {
        return DB::transaction(function () use ($registerId, $physicalBalance, $notes) {
            $register = CashRegister::where('id', $registerId)->lockForUpdate()->firstOrFail();

            if ($register->status === CashRegisterStatus::Closed) {
                throw new Exception("Shift kasir ini sudah ditutup sebelumnya.");
            }

            // Hitung total uang tunai bersih yang masuk selama shift ini (Uang Tunai Masuk dikurangi Kembalian yang keluar dari laci)
            $sales = Sale::with('payments')
                ->where('cash_register_id', $register->id)
                ->where('status', SaleStatus::Completed)
                ->get();

            $netCashSales = 0.0;
            foreach ($sales as $sale) {
                $cashPaid = $sale->payments->where('payment_method', PaymentMethod::Cash)->sum('amount');
                if ($cashPaid > 0) {
                    $netCashSales += max(0, $cashPaid - (float) $sale->change_amount);
                }
            }

            $expectedBalance = (float) $register->opening_balance + $netCashSales;
            $difference = $physicalBalance - $expectedBalance;

            $register->update([
                'closing_balance' => $physicalBalance,
                'expected_balance' => $expectedBalance,
                'difference' => $difference,
                'closed_at' => now(),
                'status' => CashRegisterStatus::Closed,
                'notes' => $notes,
            ]);

            return $register;
        });
    }

    /**
     * Dapatkan register aktif untuk kasir saat ini.
     */
    public function getActiveRegister(int $branchId, int $userId): ?CashRegister
    {
        return CashRegister::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->where('status', CashRegisterStatus::Open)
            ->first();
    }

    /**
     * Konversi PKB yang berstatus ReadyForPayment ke format keranjang POS.
     */
    public function loadWorkOrderToCart(int $woId): array
    {
        $wo = WorkOrder::with(['customer', 'vehicle.motorModel', 'items.product', 'items.service'])->findOrFail($woId);

        if ($wo->status !== WorkOrderStatus::ReadyForPayment && $wo->status !== WorkOrderStatus::InProgress) {
            throw new Exception("Hanya PKB yang siap bayar yang dapat ditarik ke kasir.");
        }

        $items = [];
        foreach ($wo->items as $item) {
            if ($item->item_type === 'product' && $item->product) {
                $items[] = [
                    'id' => 'prod_' . $item->product_id,
                    'item_type' => 'product',
                    'item_id' => $item->product_id,
                    'code' => $item->product->sku,
                    'name' => $item->product->name,
                    'unit' => $item->product->unit,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'mechanic_id' => $item->mechanic_id,
                    'commission_amount' => 0.0,
                ];
            } elseif ($item->item_type === 'service' && $item->service) {
                $items[] = [
                    'id' => 'srv_' . $item->service_id,
                    'item_type' => 'service',
                    'item_id' => $item->service_id,
                    'code' => $item->service->code,
                    'name' => $item->service->name,
                    'unit' => 'Jasa',
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'mechanic_id' => $item->mechanic_id,
                    'commission_amount' => (float) $item->commission_amount,
                ];
            }
        }

        return [
            'work_order_id' => $wo->id,
            'customer_id' => $wo->customer_id,
            'price_tier_id' => $wo->customer?->price_tier_id ?? PriceTier::where('is_default', true)->value('id'),
            'label' => "{$wo->vehicle->plate_number} - {$wo->customer?->name}",
            'items' => $items,
        ];
    }

    /**
     * Simpan transaksi ke Hold / Suspend Bill.
     */
    public function suspendBill(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $invoiceNumber = 'HLD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $data['branch_id'],
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'user_id' => $data['user_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'work_order_id' => $data['work_order_id'] ?? null,
                'price_tier_id' => $data['price_tier_id'],
                'status' => SaleStatus::Suspended,
                'hold_label' => $data['hold_label'] ?? 'Transaksi Pending',
                'total_gross' => $data['total_gross'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'grand_total' => $data['grand_total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $costSnapshot = 0.0;
                if ($item['item_type'] === 'product') {
                    $costSnapshot = (float) Product::where('id', $item['item_id'])->value('moving_avg_cost');
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_type' => $item['item_type'],
                    'product_id' => $item['item_type'] === 'product' ? $item['item_id'] : null,
                    'service_id' => $item['item_type'] === 'service' ? $item['item_id'] : null,
                    'mechanic_id' => $item['mechanic_id'] ?? null,
                    'cost_snapshot' => $costSnapshot,
                    'unit_price' => $item['unit_price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                    'mechanic_commission' => $item['commission_amount'] ?? 0,
                ]);
            }

            return $sale;
        });
    }

    /**
     * Proses Checkout / Pembayaran Transaksi POS (Lunas).
     */
    public function checkout(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $datePrefix = date('Ymd');
            $randomSuffix = strtoupper(substr(uniqid(), -4));
            $invoiceNumber = "INV-{$datePrefix}-{$randomSuffix}";

            $totalGross = (float) $data['total_gross'];
            $discount = (float) ($data['discount_amount'] ?? 0);
            $tax = (float) ($data['tax_amount'] ?? 0);
            $grandTotal = (float) $data['grand_total'];

            $payments = $data['payments'] ?? [];
            $totalPaid = 0.0;
            foreach ($payments as $p) {
                $totalPaid += (float) $p['amount'];
            }

            if ($totalPaid < $grandTotal) {
                throw new Exception("Pembayaran kurang! Total tagihan: Rp " . number_format($grandTotal, 0, ',', '.') . ", total bayar: Rp " . number_format($totalPaid, 0, ',', '.'));
            }

            $changeAmount = $totalPaid - $grandTotal;

            // Jika sebelumnya ini adalah Hold Bill, update atau hapus data suspend lama
            if (! empty($data['suspended_sale_id'])) {
                Sale::where('id', $data['suspended_sale_id'])->delete();
            }

            // 1. Buat Header Transaksi
            $sale = Sale::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $data['branch_id'],
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'user_id' => $data['user_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'work_order_id' => $data['work_order_id'] ?? null,
                'price_tier_id' => $data['price_tier_id'],
                'status' => SaleStatus::Completed,
                'total_gross' => $totalGross,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'total_paid' => $totalPaid,
                'change_amount' => $changeAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            // 2. Simpan Detail Items, Snapshot Modal HPP, Komisi Mekanik & Kurangi Stok
            foreach ($data['items'] as $item) {
                $costSnapshot = 0.0;
                $commission = (float) ($item['commission_amount'] ?? 0);

                if ($item['item_type'] === 'product') {
                    $product = Product::findOrFail($item['item_id']);
                    $costSnapshot = (float) $product->moving_avg_cost;

                    // Kurangi stok cabang secara atomik
                    $this->stockService->decreaseStock(
                        branchId: $sale->branch_id,
                        productId: $product->id,
                        qty: (float) $item['qty'],
                        type: StockMovementType::SaleOut,
                        referenceType: Sale::class,
                        referenceId: $sale->id,
                        userId: $sale->user_id,
                        notes: "Penjualan Kasir POS No. {$sale->invoice_number}"
                    );
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_type' => $item['item_type'],
                    'product_id' => $item['item_type'] === 'product' ? $item['item_id'] : null,
                    'service_id' => $item['item_type'] === 'service' ? $item['item_id'] : null,
                    'mechanic_id' => $item['mechanic_id'] ?? null,
                    'cost_snapshot' => $costSnapshot,
                    'unit_price' => (float) $item['unit_price'],
                    'qty' => (float) $item['qty'],
                    'subtotal' => (float) $item['subtotal'],
                    'mechanic_commission' => $commission,
                ]);
            }

            // 3. Simpan Rincian Pembayaran (Split Payment)
            foreach ($payments as $payment) {
                $method = is_string($payment['payment_method'])
                    ? PaymentMethod::from($payment['payment_method'])
                    : $payment['payment_method'];

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $method,
                    'amount' => (float) $payment['amount'],
                    'reference_number' => $payment['reference_number'] ?? null,
                ]);
            }

            // 4. Jika transaksi berasal dari Work Order (PKB), tandai PKB Completed
            if ($sale->work_order_id) {
                WorkOrder::where('id', $sale->work_order_id)->update([
                    'status' => WorkOrderStatus::Completed,
                    'completed_at' => now(),
                ]);
            }

            return $sale->load(['items.product', 'items.service', 'payments', 'customer', 'branch']);
        });
    }
}
