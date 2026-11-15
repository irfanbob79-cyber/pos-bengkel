<x-filament-panels::page>
    <div class="flex flex-col h-[calc(100vh-12rem)] space-y-4">
        <!-- TOP CONTROL BAR -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
            <!-- Price Tier Switcher (Multi-Harga Dinamis Instan) -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tier Harga:</span>
                <div class="flex gap-1.5 flex-wrap">
                    @foreach(\App\Models\PriceTier::where('is_active', true)->get() as $tier)
                        <button 
                            type="button" 
                            wire:click="changePriceTier({{ $tier->id }})"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $price_tier_id === $tier->id ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}"
                        >
                            {{ $tier->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Pelanggan & Antrean Tools -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Tombol PKB Siap Bayar -->
                <button 
                    type="button" 
                    wire:click="$set('showWorkOrdersModal', true)"
                    class="relative px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-600 text-white shadow-sm transition flex items-center gap-1.5"
                >
                    <x-heroicon-o-wrench-screwdriver class="w-4 h-4"/>
                    Antrean PKB Siap Bayar
                    @if($this->getReadyWorkOrdersCount() > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-red-600 text-white font-bold rounded-full">
                            {{ $this->getReadyWorkOrdersCount() }}
                        </span>
                    @endif
                </button>

                <!-- Tombol Hold Bills -->
                <button 
                    type="button" 
                    wire:click="$set('showHoldBillsModal', true)"
                    class="relative px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white shadow-sm transition flex items-center gap-1.5"
                >
                    <x-heroicon-o-pause class="w-4 h-4"/>
                    Daftar Hold Bills
                    @if($this->getHoldBillsCount() > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-red-600 text-white font-bold rounded-full">
                            {{ $this->getHoldBillsCount() }}
                        </span>
                    @endif
                </button>

                <!-- Shift Status -->
                @if($is_register_open)
                    <button 
                        type="button" 
                        wire:click="$set('showCloseRegisterModal', true)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 hover:bg-emerald-200 transition"
                    >
                        Shift Aktif (Tutup Shift)
                    </button>
                @else
                    <button 
                        type="button" 
                        wire:click="$set('showOpenRegisterModal', true)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-500 text-white hover:bg-red-600 transition"
                    >
                        Shift Tertutup (Buka Shift)
                    </button>
                @endif
            </div>
        </div>

        <!-- MAIN POS WORKSPACE: DUA KOLOM -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 flex-1 overflow-hidden">
            <!-- KOLOM KIRI (7 KANTONG): KATALOG BARCODE & ITEM -->
            <div class="lg:col-span-7 flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 overflow-hidden">
                <!-- Search Barcode Auto-Focus -->
                <div class="mb-3">
                    <form wire:submit.prevent="handleBarcodeInput" class="relative">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
                        <input 
                            type="text" 
                            id="posScannerInput"
                            wire:model="search_query"
                            placeholder="Scan Barcode tembak / Ketik SKU atau nama produk lalu tekan Enter..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-lg border-2 border-primary-500 font-mono text-sm dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-400"
                            autofocus
                        />
                    </form>
                </div>

                <!-- Category Pills -->
                <div class="flex gap-1.5 overflow-x-auto pb-2 mb-3 text-xs scrollbar-thin">
                    <button 
                        type="button" 
                        wire:click="$set('selected_category', 'all')"
                        class="px-3 py-1.5 rounded-full whitespace-nowrap font-medium transition {{ $selected_category === 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                    >
                        Semua
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('selected_category', 'service')"
                        class="px-3 py-1.5 rounded-full whitespace-nowrap font-medium transition {{ $selected_category === 'service' ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                    >
                        Jasa Servis Bengkel
                    </button>
                    @foreach(\App\Models\Category::all() as $cat)
                        <button 
                            type="button" 
                            wire:click="$set('selected_category', '{{ $cat->id }}')"
                            class="px-3 py-1.5 rounded-full whitespace-nowrap font-medium transition {{ $selected_category == $cat->id ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Grid Item Produk & Jasa -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @forelse($this->getCatalogItems() as $item)
                            <div 
                                wire:click="{{ $item['type'] === 'service' ? "addServiceToCart({$item['id']})" : "addProductToCart({$item['id']})" }}"
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-400 bg-gray-50/50 dark:bg-gray-700/30 hover:bg-primary-50/30 dark:hover:bg-primary-900/10 cursor-pointer transition flex flex-col justify-between select-none"
                            >
                                <div>
                                    <div class="flex justify-between items-start gap-1">
                                        <span class="text-[10px] font-mono font-bold text-gray-400 uppercase tracking-tight">
                                            {{ $item['code'] }}
                                        </span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-semibold rounded {{ $item['type'] === 'service' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-semibold text-gray-900 dark:text-white mt-1 line-clamp-2 leading-tight">
                                        {{ $item['name'] }}
                                    </h4>
                                </div>
                                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700/50 flex justify-between items-center">
                                    <span class="text-xs font-bold text-primary-600 dark:text-primary-400">
                                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                                    </span>
                                    @if($item['stock'] !== null)
                                        <span class="text-[10px] {{ $item['stock'] <= 5 ? 'text-red-500 font-bold' : 'text-gray-400' }}">
                                            Stok: {{ $item['stock'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-gray-400">
                                Tidak ada produk atau jasa yang cocok dengan pencarian.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (5 KANTONG): KERANJANG BELANJA & PEMBAYARAN -->
            <div class="lg:col-span-5 flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 overflow-hidden">
                <!-- Customer Header -->
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700 mb-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user class="w-4 h-4 text-gray-400"/>
                        <select wire:model.live="customer_id" class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white py-1">
                            <option value="">Pelanggan Umum (Tanpa Nama)</option>
                            @foreach(\App\Models\Customer::all() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!empty($cart))
                        <button type="button" wire:click="clearCart" class="text-xs text-red-500 hover:underline">
                            Reset Keranjang
                        </button>
                    @endif
                </div>

                <!-- Daftar Item Keranjang -->
                <div class="flex-1 overflow-y-auto pr-1 space-y-2">
                    @forelse($cart as $key => $item)
                        <div class="p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 flex items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold {{ $item['item_type'] === 'service' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $item['unit'] }}
                                    </span>
                                    <h5 class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </h5>
                                </div>
                                <span class="text-[11px] text-gray-500">
                                    @ Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                </span>
                            </div>

                            <!-- Qty Control -->
                            <div class="flex items-center gap-1">
                                <button 
                                    type="button" 
                                    wire:click="updateQty('{{ $key }}', {{ $item['qty'] - 1 }})"
                                    class="w-6 h-6 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 font-bold flex items-center justify-center text-xs"
                                >
                                    -
                                </button>
                                <span class="w-8 text-center text-xs font-bold">
                                    {{ $item['qty'] }}
                                </span>
                                <button 
                                    type="button" 
                                    wire:click="updateQty('{{ $key }}', {{ $item['qty'] + 1 }})"
                                    class="w-6 h-6 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 font-bold flex items-center justify-center text-xs"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Subtotal & Remove -->
                            <div class="text-right min-w-[70px]">
                                <div class="text-xs font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="removeFromCart('{{ $key }}')" 
                                    class="text-[10px] text-red-500 hover:underline"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 text-gray-400">
                            <x-heroicon-o-shopping-cart class="w-12 h-12 mx-auto mb-2 opacity-50"/>
                            Keranjang transaksi masih kosong.
                        </div>
                    @endforelse
                </div>

                <!-- Ringkasan Kalkulasi & Tombol Bayar -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700 mt-2 space-y-2">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Total Kotor</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">
                            Rp {{ number_format($this->getTotalGross(), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>Diskon (Rp)</span>
                        <input 
                            type="number" 
                            wire:model.live="discount_amount" 
                            class="w-24 px-2 py-1 text-right text-xs rounded border-gray-300 dark:bg-gray-700 dark:text-white"
                            placeholder="0"
                        />
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white pt-1 border-t border-dashed border-gray-200 dark:border-gray-700">
                        <span>Grand Total</span>
                        <span class="text-xl text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($this->getGrandTotal(), 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Tombol Aksi Kasir -->
                    <div class="grid grid-cols-3 gap-2 pt-2">
                        <button 
                            type="button" 
                            wire:click="suspendCurrentBill"
                            class="py-2.5 px-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs rounded-lg transition flex items-center justify-center gap-1"
                        >
                            <x-heroicon-o-pause class="w-4 h-4"/>
                            Hold Bill
                        </button>
                        <button 
                            type="button" 
                            wire:click="openPaymentModal"
                            class="col-span-2 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-lg shadow transition flex items-center justify-center gap-2"
                        >
                            <x-heroicon-o-credit-card class="w-5 h-5"/>
                            Bayar / Checkout (F10)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PEMBAYARAN (SPLIT PAYMENT) -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pembayaran Transaksi</h3>
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="p-3 bg-primary-50 dark:bg-primary-900/30 rounded-xl text-center">
                    <span class="text-xs text-primary-700 dark:text-primary-300 font-semibold uppercase tracking-wider block">Total Tagihan</span>
                    <span class="text-3xl font-black text-primary-600 dark:text-primary-400">
                        Rp {{ number_format($this->getGrandTotal(), 0, ',', '.') }}
                    </span>
                </div>

                <!-- Input Metode Pembayaran (Split Payment) -->
                <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                    @foreach($payments as $index => $pay)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 space-y-2">
                            <div class="flex gap-2">
                                <select wire:model.live="payments.{{ $index }}.payment_method" class="w-1/3 text-xs rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="qris">QRIS</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                    <option value="debit_card">Kartu Debit</option>
                                    <option value="credit_card">Kartu Kredit</option>
                                </select>
                                <input 
                                    type="number" 
                                    wire:model.live="payments.{{ $index }}.amount" 
                                    class="w-2/3 text-sm font-bold rounded border-gray-300 dark:bg-gray-700 dark:text-white text-right"
                                    placeholder="Nominal bayar..."
                                />
                                @if(count($payments) > 1)
                                    <button type="button" wire:click="removePaymentRow({{ $index }})" class="text-red-500 font-bold px-1">&times;</button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <!-- Tombol Cepat Uang Pas & Pecahan -->
                    <div class="flex gap-1.5 flex-wrap pt-1">
                        <button type="button" wire:click="setQuickCash({{ $this->getGrandTotal() }})" class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded font-semibold">Uang Pas</button>
                        <button type="button" wire:click="setQuickCash(50000)" class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded font-semibold">50.000</button>
                        <button type="button" wire:click="setQuickCash(100000)" class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded font-semibold">100.000</button>
                        <button type="button" wire:click="setQuickCash(200000)" class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded font-semibold">200.000</button>
                    </div>

                    <button type="button" wire:click="addSplitPaymentRow" class="text-xs text-primary-600 hover:underline flex items-center gap-1">
                        + Tambah Metode Split Payment Lain
                    </button>
                </div>

                <!-- Kembalian -->
                <div class="flex justify-between items-center p-3 rounded-lg bg-gray-100 dark:bg-gray-700/50">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Kembalian:</span>
                    <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($this->getChangeAmount(), 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Batal</button>
                    <button type="button" wire:click="submitCheckout" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow">
                        Selesaikan Transaksi
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL CETAK STRUK THERMAL -->
    @if($showReceiptModal && $lastCompletedSale)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-2 text-center">
                    <h4 class="font-bold text-gray-900 dark:text-white text-base">TRANSAKSI SELESAI</h4>
                    <p class="text-xs text-gray-500 font-mono">{{ $lastCompletedSale->invoice_number }}</p>
                </div>

                <!-- Area Cetak Struk 58mm/80mm -->
                <div id="receipt-print-area" class="p-4 bg-gray-50 text-gray-900 font-mono text-xs rounded border border-gray-200">
                    <div class="text-center border-b border-dashed border-gray-400 pb-2 mb-2">
                        <div class="font-bold text-sm">{{ $lastCompletedSale->branch->name }}</div>
                        <div class="text-[10px]">{{ $lastCompletedSale->branch->address }}</div>
                        <div class="text-[10px]">{{ $lastCompletedSale->created_at->format('d/m/Y H:i') }}</div>
                    </div>

                    <div class="space-y-1 border-b border-dashed border-gray-400 pb-2 mb-2">
                        @foreach($lastCompletedSale->items as $it)
                            <div class="flex justify-between">
                                <span class="truncate">{{ $it->product?->name ?? $it->service?->name }}</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-gray-600">
                                <span>{{ $it->qty }} x {{ number_format($it->unit_price, 0, ',', '.') }}</span>
                                <span>Rp {{ number_format($it->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-1 text-right">
                        <div class="flex justify-between font-bold">
                            <span>TOTAL:</span>
                            <span>Rp {{ number_format($lastCompletedSale->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span>DIBAYAR:</span>
                            <span>Rp {{ number_format($lastCompletedSale->total_paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span>KEMBALI:</span>
                            <span>Rp {{ number_format($lastCompletedSale->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="text-center text-[10px] mt-4 pt-2 border-t border-dashed border-gray-400">
                        *** TERIMA KASIH ***<br>
                        Barang yang sudah dibeli tidak dapat ditukar/dikembalikan
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showReceiptModal', false)" class="px-4 py-2 text-sm text-gray-600">Tutup</button>
                    <button type="button" onclick="window.print()" class="px-5 py-2 bg-primary-600 text-white font-bold rounded-lg shadow">
                        Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DAFTAR HOLD BILLS -->
    @if($showHoldBillsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Daftar Tagihan Tertunda (Hold Bills)</h3>
                    <button type="button" wire:click="$set('showHoldBillsModal', false)" class="text-gray-400">&times;</button>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse(\App\Models\Sale::where('branch_id', $branch_id)->where('status', \App\Enums\SaleStatus::Suspended)->latest()->get() as $hld)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-sm text-gray-900 dark:text-white block">{{ $hld->hold_label }}</span>
                                <span class="text-xs text-gray-500 font-mono">{{ $hld->invoice_number }} &bull; {{ $hld->created_at->format('H:i') }}</span>
                                <span class="text-xs font-bold text-primary-600 block mt-1">Rp {{ number_format($hld->grand_total, 0, ',', '.') }}</span>
                            </div>
                            <button 
                                type="button" 
                                wire:click="resumeSuspendedBill({{ $hld->id }})" 
                                class="px-3 py-1.5 text-xs font-bold bg-primary-600 hover:bg-primary-700 text-white rounded-lg"
                            >
                                Buka Tagihan
                            </button>
                        </div>
                    @empty
                        <p class="text-center py-8 text-gray-400 text-sm">Tidak ada tagihan yang di-hold.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL ANTREAN PKB SIAP BAYAR -->
    @if($showWorkOrdersModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Antrean Servis Selesai (Siap Bayar)</h3>
                    <button type="button" wire:click="$set('showWorkOrdersModal', false)" class="text-gray-400">&times;</button>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse(\App\Models\WorkOrder::where('branch_id', $branch_id)->where('status', \App\Enums\WorkOrderStatus::ReadyForPayment)->latest()->get() as $rwo)
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-sm text-gray-900 dark:text-white block">
                                    {{ $rwo->vehicle->plate_number }} ({{ $rwo->vehicle->motorModel->name }})
                                </span>
                                <span class="text-xs text-gray-500">Pemilik: {{ $rwo->customer->name ?? '-' }}</span>
                                <span class="text-xs font-bold text-primary-600 block mt-1">
                                    Total: Rp {{ number_format($rwo->calculateTotal(), 0, ',', '.') }}
                                </span>
                            </div>
                            <button 
                                type="button" 
                                wire:click="pullWorkOrderToCart({{ $rwo->id }})" 
                                class="px-3 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center gap-1"
                            >
                                <x-heroicon-o-arrow-right class="w-4 h-4"/>
                                Tarik ke POS
                            </button>
                        </div>
                    @empty
                        <p class="text-center py-8 text-gray-400 text-sm">Tidak ada motor yang siap ditagihkan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL BUKA REGISTER SHIFT -->
    @if($showOpenRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b pb-2">Buka Shift Kasir (Cash Register)</h3>
                <p class="text-xs text-gray-500">Masukkan nominal uang modal awal yang ada di laci kasir saat ini.</p>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Modal Awal Laci (Rp)</label>
                    <input 
                        type="number" 
                        wire:model="opening_balance_input" 
                        class="w-full text-lg font-bold rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white"
                        placeholder="Contoh: 200000"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="openRegisterSubmit" class="w-full py-2.5 bg-primary-600 text-white font-bold rounded-lg shadow">
                        Buka Shift & Masuk POS
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL TUTUP REGISTER SHIFT -->
    @if($showCloseRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b pb-2">Tutup Shift Kasir (Rekonsiliasi)</h3>
                <p class="text-xs text-gray-500">Hitung total uang fisik tunai di laci kasir saat ini sebelum mengakhiri shift.</p>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Total Uang Fisik di Laci (Rp)</label>
                    <input 
                        type="number" 
                        wire:model="closing_balance_input" 
                        class="w-full text-lg font-bold rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white"
                        placeholder="Total uang fisik..."
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCloseRegisterModal', false)" class="px-4 py-2 text-sm text-gray-600">Batal</button>
                    <button type="button" wire:click="closeRegisterSubmit" class="px-6 py-2 bg-red-600 text-white font-bold rounded-lg shadow">
                        Tutup Shift Kasir
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scanner = document.getElementById('posScannerInput');
            if (scanner) scanner.focus();

            Livewire.on('focus-scanner', () => {
                const scanner = document.getElementById('posScannerInput');
                if (scanner) {
                    scanner.focus();
                    scanner.select();
                }
            });
        });
    </script>
</x-filament-panels::page>
