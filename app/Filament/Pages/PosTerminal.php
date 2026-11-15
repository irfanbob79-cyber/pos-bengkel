<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Enums\PriceTier;
use App\Enums\SaleStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PriceTier as PriceTierModel;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Services\PosCheckoutService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PosTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Terminal Kasir (Web POS)';

    protected static ?string $title = 'Terminal Kasir POS';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.pos-terminal';

    // State Shift Kasir
    public ?int $branch_id = null;
    public ?int $cash_register_id = null;
    public bool $is_register_open = false;
    public float $opening_balance_input = 0;
    public float $closing_balance_input = 0;

    // State POS & Pelanggan
    public ?int $customer_id = null;
    public ?int $price_tier_id = null;
    public ?int $work_order_id = null;
    public ?int $suspended_sale_id = null;

    // State Pencarian & Katalog
    public string $search_query = '';
    public string $selected_category = 'all'; // 'all', 'service', or category_id

    // State Keranjang Transaksi
    public array $cart = [];
    public float $discount_amount = 0;
    public float $tax_amount = 0;

    // State Pembayaran
    public bool $showPaymentModal = false;
    public bool $showOpenRegisterModal = false;
    public bool $showCloseRegisterModal = false;
    public bool $showHoldBillsModal = false;
    public bool $showWorkOrdersModal = false;
    public bool $showReceiptModal = false;

    public string $hold_label_input = '';
    public array $payments = [
        ['payment_method' => 'cash', 'amount' => 0, 'reference_number' => ''],
    ];
    public ?Sale $lastCompletedSale = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->branch_id = $user->branch_id ?? Branch::first()?->id ?? 1;

        // Cek shift kasir aktif
        $posService = app(PosCheckoutService::class);
        $activeRegister = $posService->getActiveRegister($this->branch_id, $user->id);

        if ($activeRegister) {
            $this->cash_register_id = $activeRegister->id;
            $this->is_register_open = true;
        } else {
            $this->is_register_open = false;
            $this->showOpenRegisterModal = true;
        }

        // Set default price tier (Eceran)
        $defaultTier = PriceTierModel::where('is_default', true)->first();
        $this->price_tier_id = $defaultTier?->id;
    }

    public function openRegisterSubmit(): void
    {
        try {
            $posService = app(PosCheckoutService::class);
            $register = $posService->openRegister(
                branchId: (int) $this->branch_id,
                userId: Auth::id(),
                openingBalance: (float) $this->opening_balance_input,
                notes: 'Buka Shift POS'
            );

            $this->cash_register_id = $register->id;
            $this->is_register_open = true;
            $this->showOpenRegisterModal = false;

            Notification::make()->title('Shift Kasir Dibuka!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal membuka shift!')->body($e->getMessage())->danger()->send();
        }
    }

    public function closeRegisterSubmit(): void
    {
        try {
            $posService = app(PosCheckoutService::class);
            $posService->closeRegister(
                registerId: (int) $this->cash_register_id,
                physicalBalance: (float) $this->closing_balance_input,
                notes: 'Tutup Shift Kasir'
            );

            $this->is_register_open = false;
            $this->cash_register_id = null;
            $this->showCloseRegisterModal = false;

            Notification::make()->title('Shift Kasir Ditutup & Direkonsiliasi!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal menutup shift!')->body($e->getMessage())->danger()->send();
        }
    }

    public function handleBarcodeInput(): void
    {
        $query = trim($this->search_query);
        if (empty($query)) return;

        // Cari berdasarkan barcode atau SKU
        $product = Product::where('barcode', $query)
            ->orWhere('sku', $query)
            ->first();

        if ($product) {
            $this->addProductToCart($product->id);
            $this->search_query = '';
            $this->dispatch('focus-scanner');
            return;
        }

        // Jika bukan barcode, biarkan query memfilter grid
    }

    public function addProductToCart(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $key = 'prod_' . $product->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;
        } else {
            $price = $product->getPriceForTier($this->price_tier_id, 1);
            $this->cart[$key] = [
                'id' => $key,
                'item_type' => 'product',
                'item_id' => $product->id,
                'code' => $product->sku,
                'name' => $product->name,
                'unit' => $product->unit,
                'qty' => 1,
                'unit_price' => $price,
                'subtotal' => $price,
                'mechanic_id' => null,
                'commission_amount' => 0.0,
            ];
        }

        $this->recalculateCart();
    }

    public function addServiceToCart(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        $key = 'srv_' . $service->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;
        } else {
            $price = (float) $service->default_price;
            $commission = $service->calculateCommission($price);

            $this->cart[$key] = [
                'id' => $key,
                'item_type' => 'service',
                'item_id' => $service->id,
                'code' => $service->code,
                'name' => $service->name,
                'unit' => 'Jasa',
                'qty' => 1,
                'unit_price' => $price,
                'subtotal' => $price,
                'mechanic_id' => null,
                'commission_amount' => $commission,
            ];
        }

        $this->recalculateCart();
    }

    public function updateQty(string $key, float $qty): void
    {
        if ($qty <= 0) {
            unset($this->cart[$key]);
        } else {
            $this->cart[$key]['qty'] = $qty;
        }
        $this->recalculateCart();
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
        $this->recalculateCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->work_order_id = null;
        $this->suspended_sale_id = null;
        $this->discount_amount = 0;
        $this->tax_amount = 0;
    }

    public function changePriceTier(int $tierId): void
    {
        $this->price_tier_id = $tierId;
        // Perbarui seluruh harga sparepart di keranjang sesuai tier baru
        foreach ($this->cart as $key => $item) {
            if ($item['item_type'] === 'product') {
                $product = Product::find($item['item_id']);
                if ($product) {
                    $newPrice = $product->getPriceForTier($this->price_tier_id, $item['qty']);
                    $this->cart[$key]['unit_price'] = $newPrice;
                    $this->cart[$key]['subtotal'] = $newPrice * $item['qty'];
                }
            }
        }
        $this->recalculateCart();
    }

    public function recalculateCart(): void
    {
        foreach ($this->cart as $key => $item) {
            if ($item['item_type'] === 'product') {
                $product = Product::find($item['item_id']);
                if ($product) {
                    $this->cart[$key]['unit_price'] = $product->getPriceForTier($this->price_tier_id, $item['qty']);
                }
            }
            $this->cart[$key]['subtotal'] = $this->cart[$key]['unit_price'] * $this->cart[$key]['qty'];
        }
    }

    public function pullWorkOrderToCart(int $woId): void
    {
        try {
            $posService = app(PosCheckoutService::class);
            $cartData = $posService->loadWorkOrderToCart($woId);

            $this->cart = [];
            foreach ($cartData['items'] as $item) {
                $this->cart[$item['id']] = $item;
            }

            $this->work_order_id = $cartData['work_order_id'];
            $this->customer_id = $cartData['customer_id'];
            $this->price_tier_id = $cartData['price_tier_id'];
            $this->showWorkOrdersModal = false;

            $this->recalculateCart();
            Notification::make()->title("PKB #{$cartData['label']} berhasil ditarik ke keranjang!")->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal menarik PKB!')->body($e->getMessage())->danger()->send();
        }
    }

    public function suspendCurrentBill(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang masih kosong!')->warning()->send();
            return;
        }

        try {
            $posService = app(PosCheckoutService::class);
            $posService->suspendBill([
                'branch_id' => $this->branch_id,
                'cash_register_id' => $this->cash_register_id,
                'user_id' => Auth::id(),
                'customer_id' => $this->customer_id,
                'work_order_id' => $this->work_order_id,
                'price_tier_id' => $this->price_tier_id,
                'hold_label' => $this->hold_label_input ?: 'Antrean ' . date('H:i'),
                'total_gross' => $this->getTotalGross(),
                'discount_amount' => $this->discount_amount,
                'tax_amount' => $this->tax_amount,
                'grand_total' => $this->getGrandTotal(),
                'items' => array_values($this->cart),
            ]);

            $this->clearCart();
            $this->hold_label_input = '';
            Notification::make()->title('Transaksi berhasil di-Hold / Suspend!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Gagal hold bill!')->body($e->getMessage())->danger()->send();
        }
    }

    public function resumeSuspendedBill(int $saleId): void
    {
        $sale = Sale::with('items.product', 'items.service')->findOrFail($saleId);

        $this->cart = [];
        foreach ($sale->items as $item) {
            $key = ($item->item_type === 'product' ? 'prod_' : 'srv_') . ($item->product_id ?? $item->service_id);
            $this->cart[$key] = [
                'id' => $key,
                'item_type' => $item->item_type,
                'item_id' => $item->product_id ?? $item->service_id,
                'code' => $item->product?->sku ?? $item->service?->code,
                'name' => $item->product?->name ?? $item->service?->name,
                'unit' => $item->product?->unit ?? 'Jasa',
                'qty' => (float) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'mechanic_id' => $item->mechanic_id,
                'commission_amount' => (float) $item->mechanic_commission,
            ];
        }

        $this->customer_id = $sale->customer_id;
        $this->price_tier_id = $sale->price_tier_id;
        $this->work_order_id = $sale->work_order_id;
        $this->suspended_sale_id = $sale->id;
        $this->discount_amount = (float) $sale->discount_amount;
        $this->tax_amount = (float) $sale->tax_amount;

        $this->showHoldBillsModal = false;
        Notification::make()->title("Tagihan {$sale->hold_label} dibuka kembali!")->info()->send();
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang masih kosong!')->warning()->send();
            return;
        }

        $grandTotal = $this->getGrandTotal();
        $this->payments = [
            ['payment_method' => 'cash', 'amount' => $grandTotal, 'reference_number' => ''],
        ];
        $this->showPaymentModal = true;
    }

    public function addSplitPaymentRow(): void
    {
        $this->payments[] = [
            'payment_method' => 'qris',
            'amount' => 0,
            'reference_number' => '',
        ];
    }

    public function removePaymentRow(int $index): void
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
    }

    public function setQuickCash(float $amount): void
    {
        $this->payments[0]['amount'] = $amount;
    }

    public function submitCheckout(): void
    {
        try {
            $posService = app(PosCheckoutService::class);
            $sale = $posService->checkout([
                'branch_id' => $this->branch_id,
                'cash_register_id' => $this->cash_register_id,
                'user_id' => Auth::id(),
                'customer_id' => $this->customer_id,
                'work_order_id' => $this->work_order_id,
                'price_tier_id' => $this->price_tier_id,
                'suspended_sale_id' => $this->suspended_sale_id,
                'total_gross' => $this->getTotalGross(),
                'discount_amount' => $this->discount_amount,
                'tax_amount' => $this->tax_amount,
                'grand_total' => $this->getGrandTotal(),
                'payments' => $this->payments,
                'items' => array_values($this->cart),
            ]);

            $this->lastCompletedSale = $sale;
            $this->showPaymentModal = false;
            $this->showReceiptModal = true;
            $this->clearCart();

            Notification::make()->title('Transaksi Berhasil Diselesaikan!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Pembayaran Gagal!')->body($e->getMessage())->danger()->send();
        }
    }

    public function getTotalGross(): float
    {
        return array_sum(array_column($this->cart, 'subtotal'));
    }

    public function getGrandTotal(): float
    {
        return max(0, $this->getTotalGross() - $this->discount_amount + $this->tax_amount);
    }

    public function getTotalPaid(): float
    {
        return array_sum(array_column($this->payments, 'amount'));
    }

    public function getChangeAmount(): float
    {
        return max(0, $this->getTotalPaid() - $this->getGrandTotal());
    }

    public function getCatalogItems(): array
    {
        $query = $this->search_query;
        $cat = $this->selected_category;

        $items = [];

        // Jika bukan filter kategori spesifik sparepart, tampilkan juga Jasa Servis
        if ($cat === 'all' || $cat === 'service') {
            $services = Service::where('is_active', true)
                ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('code', 'like', "%{$query}%"))
                ->limit(12)
                ->get();

            foreach ($services as $s) {
                $items[] = [
                    'type' => 'service',
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'price' => (float) $s->default_price,
                    'badge' => 'Jasa',
                    'badge_color' => 'warning',
                    'stock' => null,
                ];
            }
        }

        // Tampilkan Sparepart
        if ($cat !== 'service') {
            $products = Product::where('is_active', true)
                ->when($cat !== 'all', fn ($q) => $q->where('category_id', $cat))
                ->when($query, function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                })
                ->limit(24)
                ->get();

            foreach ($products as $p) {
                $stock = (float) $p->branchStocks()->where('branch_id', $this->branch_id)->value('qty');
                $price = $p->getPriceForTier($this->price_tier_id, 1);

                $items[] = [
                    'type' => 'product',
                    'id' => $p->id,
                    'code' => $p->sku,
                    'name' => $p->name,
                    'price' => $price,
                    'badge' => $p->unit,
                    'badge_color' => 'info',
                    'stock' => $stock,
                ];
            }
        }

        return $items;
    }

    public function getReadyWorkOrdersCount(): int
    {
        return WorkOrder::where('branch_id', $this->branch_id)
            ->where('status', WorkOrderStatus::ReadyForPayment)
            ->count();
    }

    public function getHoldBillsCount(): int
    {
        return Sale::where('branch_id', $this->branch_id)
            ->where('status', SaleStatus::Suspended)
            ->count();
    }
}
