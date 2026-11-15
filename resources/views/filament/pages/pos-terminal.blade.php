<x-filament-panels::page>
    <div class="flex flex-col min-h-[calc(100vh-9.5rem)] lg:h-[calc(100vh-9.5rem)] space-y-3.5">
        <!-- TOP CONTROL BAR: GLASSMORPHISM & MODERN ACTIONS -->
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md p-3.5 rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 flex flex-wrap items-center justify-between gap-3">
            <!-- Price Tier Switcher (Multi-Harga Dinamis Instan) -->
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700/60 text-gray-500 dark:text-gray-400">
                    <x-heroicon-m-tag class="w-3.5 h-3.5"/>
                    <span class="text-[11px] font-bold uppercase tracking-wider">Tier Harga:</span>
                </div>
                <div class="flex gap-1.5 flex-wrap">
                    @foreach(\App\Models\PriceTier::where('is_active', true)->get() as $tier)
                        <button 
                            type="button" 
                            wire:click="changePriceTier({{ $tier->id }})"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all duration-150 {{ $price_tier_id === $tier->id ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25 ring-2 ring-indigo-400/40' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300' }}"
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
                    class="relative px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-md shadow-amber-500/20 transition-all duration-150 flex items-center gap-1.5"
                >
                    <x-heroicon-o-wrench-screwdriver class="w-4 h-4"/>
                    Antrean PKB Siap Bayar
                    @if($this->getReadyWorkOrdersCount() > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-red-600 text-white font-black rounded-full animate-pulse">
                            {{ $this->getReadyWorkOrdersCount() }}
                        </span>
                    @endif
                </button>

                <!-- Tombol Hold Bills -->
                <button 
                    type="button" 
                    wire:click="$set('showHoldBillsModal', true)"
                    class="relative px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 border border-gray-300/80 dark:border-gray-600 transition-all duration-150 flex items-center gap-1.5"
                >
                    <x-heroicon-o-pause class="w-4 h-4 text-indigo-500"/>
                    Hold Bills
                    @if($this->getHoldBillsCount() > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-indigo-600 text-white font-bold rounded-full">
                            {{ $this->getHoldBillsCount() }}
                        </span>
                    @endif
                </button>

                <!-- Shift Status -->
                @if($is_register_open)
                    <button 
                        type="button" 
                        wire:click="$set('showCloseRegisterModal', true)"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 transition flex items-center gap-1.5"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        Shift Aktif (Tutup)
                    </button>
                @else
                    <button 
                        type="button" 
                        wire:click="$set('showOpenRegisterModal', true)"
                        class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-500/20 transition flex items-center gap-1.5"
                    >
                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5"/>
                        Shift Tutup (Buka Shift)
                    </button>
                @endif
            </div>
        </div>

        <!-- MAIN POS WORKSPACE: DUA KOLOM RESPONSIVE -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 flex-1 overflow-hidden">
            <!-- KOLOM KIRI (7 KANTONG): KATALOG BARCODE & ITEM -->
            <div class="lg:col-span-7 flex flex-col bg-white/95 dark:bg-gray-800/95 backdrop-blur rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 p-4 overflow-hidden">
                <!-- Search Barcode Auto-Focus Modern -->
                <div class="mb-3">
                    <form wire:submit.prevent="handleBarcodeInput" class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-indigo-500">
                            <x-heroicon-o-qr-code class="w-5 h-5"/>
                        </div>
                        <input 
                            type="text" 
                            id="posScannerInput"
                            wire:model="search_query"
                            placeholder="Scan Barcode / Tekan Enter untuk cari SKU atau nama produk..."
                            class="w-full pl-11 pr-24 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50/70 dark:bg-gray-900/50 text-gray-900 dark:text-white font-mono text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-900 transition-all shadow-inner"
                            autofocus
                        />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <kbd class="hidden sm:inline px-1.5 py-0.5 text-[10px] font-mono text-gray-400 bg-gray-200 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600">ENTER</kbd>
                        </div>
                    </form>
                </div>

                <!-- Category Pills Modern -->
                <div class="flex gap-1.5 overflow-x-auto pb-2 mb-3 text-xs scrollbar-thin">
                    <button 
                        type="button" 
                        wire:click="$set('selected_category', 'all')"
                        class="px-3 py-1.5 rounded-xl whitespace-nowrap font-bold transition-all duration-150 {{ $selected_category === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700/80 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                    >
                        Semua Katalog
                    </button>
                    <button 
                        type="button" 
                        wire:click="$set('selected_category', 'service')"
                        class="px-3 py-1.5 rounded-xl whitespace-nowrap font-bold transition-all duration-150 flex items-center gap-1 {{ $selected_category === 'service' ? 'bg-amber-600 text-white shadow-sm' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100' }}"
                    >
                        <x-heroicon-m-wrench class="w-3.5 h-3.5"/>
                        Jasa Bengkel
                    </button>
                    @foreach(\App\Models\Category::all() as $cat)
                        <button 
                            type="button" 
                            wire:click="$set('selected_category', '{{ $cat->id }}')"
                            class="px-3 py-1.5 rounded-xl whitespace-nowrap font-semibold transition-all duration-150 {{ $selected_category == $cat->id ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700/80 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>

                <!-- Grid Item Produk & Jasa -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
                        @forelse($this->getCatalogItems() as $item)
                            <div 
                                wire:click="{{ $item['type'] === 'service' ? "addServiceToCart({$item['id']})" : "addProductToCart({$item['id']})" }}"
                                class="group relative p-3 rounded-xl border border-gray-200 dark:border-gray-700/80 hover:border-indigo-500/80 dark:hover:border-indigo-400 bg-white dark:bg-gray-800/90 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 flex flex-col justify-between cursor-pointer select-none"
                            >
                                <div>
                                    <div class="flex justify-between items-start gap-1">
                                        <span class="text-[9px] font-mono font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tight truncate max-w-[80px]">
                                            {{ $item['code'] }}
                                        </span>
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-md {{ $item['type'] === 'service' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    </div>
                                    <h4 class="text-xs font-semibold text-gray-900 dark:text-white mt-1.5 line-clamp-2 leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ $item['name'] }}
                                    </h4>
                                </div>
                                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">
                                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                                    </span>
                                    @if($item['stock'] !== null)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold {{ $item['stock'] <= 5 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' }}">
                                            {{ $item['stock'] }} unit
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-magnifying-glass class="w-8 h-8 mx-auto mb-2 opacity-40"/>
                                <p class="text-xs">Tidak ada sparepart atau jasa yang cocok.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN (5 KANTONG): KERANJANG BELANJA & TOTAL CHECKOUT -->
            <div class="lg:col-span-5 flex flex-col bg-white/95 dark:bg-gray-800/95 backdrop-blur rounded-2xl shadow-sm border border-gray-200/80 dark:border-gray-700/80 p-4 overflow-hidden">
                <!-- Customer Header Modern -->
                <div class="flex items-center justify-between pb-3 border-b border-gray-200/80 dark:border-gray-700/80 mb-3">
                    <div class="flex items-center gap-2 flex-1 mr-2">
                        <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 flex-shrink-0">
                            <x-heroicon-o-user class="w-4 h-4"/>
                        </div>
                        <select wire:model.live="customer_id" class="w-full text-xs font-semibold rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Pelanggan Umum (Walk-In Customer)</option>
                            @foreach(\App\Models\Customer::all() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!empty($cart))
                        <button type="button" wire:click="clearCart" class="text-xs text-rose-500 hover:text-rose-700 font-semibold transition whitespace-nowrap">
                            Reset
                        </button>
                    @endif
                </div>

                <!-- Daftar Item Keranjang -->
                <div class="flex-1 overflow-y-auto pr-1 space-y-2">
                    @forelse($cart as $key => $item)
                        <div class="p-2.5 rounded-xl border border-gray-200/90 dark:border-gray-700/70 bg-gray-50/70 dark:bg-gray-700/30 flex items-center justify-between gap-2.5 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] px-1.5 py-0.5 rounded font-black {{ $item['item_type'] === 'service' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300' }}">
                                        {{ $item['unit'] }}
                                    </span>
                                    <h5 class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ $item['name'] }}
                                    </h5>
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-mono">
                                    @ Rp {{ number_format($item['unit_price'], 0, ',', '.') }}
                                </div>
                            </div>

                            <!-- Qty Control -->
                            <div class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-0.5">
                                <button 
                                    type="button" 
                                    wire:click="updateQty('{{ $key }}', {{ $item['qty'] - 1 }})"
                                    class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 font-bold flex items-center justify-center text-xs text-gray-700 dark:text-gray-200 transition"
                                >
                                    -
                                </button>
                                <span class="w-7 text-center text-xs font-black text-gray-900 dark:text-white">
                                    {{ $item['qty'] }}
                                </span>
                                <button 
                                    type="button" 
                                    wire:click="updateQty('{{ $key }}', {{ $item['qty'] + 1 }})"
                                    class="w-6 h-6 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 font-bold flex items-center justify-center text-xs text-gray-700 dark:text-gray-200 transition"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Subtotal & Remove -->
                            <div class="text-right min-w-[75px]">
                                <div class="text-xs font-black text-gray-900 dark:text-white">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="removeFromCart('{{ $key }}')" 
                                    class="text-[10px] text-rose-500 hover:text-rose-700 font-medium"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 text-gray-400 dark:text-gray-500">
                            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800/80 flex items-center justify-center mx-auto mb-2 text-gray-300 dark:text-gray-600">
                                <x-heroicon-o-shopping-bag class="w-7 h-7"/>
                            </div>
                            <p class="text-xs font-semibold">Keranjang masih kosong</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Klik katalog atau tembak barcode produk</p>
                        </div>
                    @endforelse
                </div>

                <!-- Ringkasan Kalkulasi & Tombol Bayar Modern -->
                <div class="pt-3 border-t border-gray-200/80 dark:border-gray-700/80 mt-2 space-y-2">
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Total Kotor (Subtotal)</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            Rp {{ number_format($this->getTotalGross(), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <span>Potongan Diskon (Rp)</span>
                        <input 
                            type="number" 
                            wire:model.live="discount_amount" 
                            class="w-28 px-2 py-1 text-right text-xs font-semibold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500"
                            placeholder="0"
                        />
                    </div>

                    <!-- Grand Total Banner Gradient -->
                    <div class="p-3 rounded-xl bg-gradient-to-r from-indigo-500/10 via-blue-500/10 to-cyan-500/10 border border-indigo-200/70 dark:border-indigo-800/50 flex justify-between items-center">
                        <span class="text-xs font-black uppercase tracking-wider text-indigo-950 dark:text-indigo-200">Total Akhir</span>
                        <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                            Rp {{ number_format($this->getGrandTotal(), 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Tombol Aksi Kasir -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <button 
                            type="button" 
                            wire:click="suspendCurrentBill"
                            class="py-2.5 px-3 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center justify-center gap-1"
                        >
                            <x-heroicon-o-pause class="w-4 h-4"/>
                            Hold Bill
                        </button>
                        <button 
                            type="button" 
                            wire:click="openPaymentModal"
                            class="col-span-2 py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2"
                        >
                            <x-heroicon-o-credit-card class="w-5 h-5"/>
                            Checkout Pembayaran (F10)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PEMBAYARAN (SPLIT PAYMENT) -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-gray-100 dark:border-gray-700 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 flex items-center justify-center">
                            <x-heroicon-o-currency-dollar class="w-5 h-5"/>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Pembayaran Kasir (Split Payment)</h3>
                    </div>
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="p-4 bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/50 dark:to-blue-950/40 rounded-xl text-center border border-indigo-200/60 dark:border-indigo-800/40">
                    <span class="text-xs text-indigo-700 dark:text-indigo-300 font-bold uppercase tracking-wider block">Tagihan Pembayaran</span>
                    <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">
                        Rp {{ number_format($this->getGrandTotal(), 0, ',', '.') }}
                    </span>
                </div>

                <!-- Input Metode Pembayaran (Split Payment) -->
                <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                    @foreach($payments as $index => $pay)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-700/30 space-y-2">
                            <div class="flex gap-2 items-center">
                                <select wire:model.live="payments.{{ $index }}.payment_method" class="w-2/5 text-xs font-semibold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="qris">QRIS Standar</option>
                                    <option value="bank_transfer">Transfer Bank</option>
                                    <option value="debit_card">Kartu Debit</option>
                                    <option value="credit_card">Kartu Kredit</option>
                                </select>
                                <input 
                                    type="number" 
                                    wire:model.live="payments.{{ $index }}.amount" 
                                    class="w-3/5 text-sm font-black rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-right"
                                    placeholder="Nominal bayar..."
                                />
                                @if(count($payments) > 1)
                                    <button type="button" wire:click="removePaymentRow({{ $index }})" class="text-rose-500 font-bold px-1.5 hover:text-rose-700">&times;</button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <!-- Tombol Cepat Pecahan Tunai -->
                    <div class="flex gap-1.5 flex-wrap pt-1">
                        <button type="button" wire:click="setQuickCash({{ $this->getGrandTotal() }})" class="px-2.5 py-1 text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300 rounded-lg font-bold">Uang Pas</button>
                        <button type="button" wire:click="setQuickCash(50000)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-lg font-semibold hover:bg-gray-200">50.000</button>
                        <button type="button" wire:click="setQuickCash(100000)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-lg font-semibold hover:bg-gray-200">100.000</button>
                        <button type="button" wire:click="setQuickCash(200000)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-lg font-semibold hover:bg-gray-200">200.000</button>
                        <button type="button" wire:click="setQuickCash(500000)" class="px-2.5 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded-lg font-semibold hover:bg-gray-200">500.000</button>
                    </div>

                    <button type="button" wire:click="addSplitPaymentRow" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                        + Tambah Metode Split Payment Lain
                    </button>
                </div>

                <!-- Kembalian -->
                <div class="flex justify-between items-center p-3 rounded-xl bg-gray-100 dark:bg-gray-700/60 border border-gray-200 dark:border-gray-600">
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Kembalian Konsumen:</span>
                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($this->getChangeAmount(), 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400">Batal</button>
                    <button type="button" wire:click="submitCheckout" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-sm rounded-xl shadow-lg shadow-emerald-500/25">
                        Konfirmasi & Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL CETAK STRUK THERMAL 58mm/80mm -->
    @if($showReceiptModal && $lastCompletedSale)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-2 text-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-1">
                        <x-heroicon-o-check class="w-6 h-6"/>
                    </div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-base">TRANSAKSI BERHASIL</h4>
                    <p class="text-xs text-gray-500 font-mono">{{ $lastCompletedSale->invoice_number }}</p>
                </div>

                <!-- Area Cetak Struk 58mm/80mm -->
                <div id="receipt-print-area" class="p-4 bg-white text-gray-900 font-mono text-xs rounded-lg border border-gray-300 shadow-inner">
                    <div class="text-center border-b border-dashed border-gray-400 pb-2 mb-2">
                        <div class="font-black text-sm uppercase">{{ $lastCompletedSale->branch->name }}</div>
                        <div class="text-[10px] text-gray-600">{{ $lastCompletedSale->branch->address }}</div>
                        <div class="text-[10px] text-gray-600">{{ $lastCompletedSale->created_at->format('d/m/Y H:i') }}</div>
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
                        <div class="flex justify-between text-[11px] font-bold">
                            <span>KEMBALI:</span>
                            <span>Rp {{ number_format($lastCompletedSale->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="text-center text-[10px] mt-4 pt-2 border-t border-dashed border-gray-400 text-gray-600">
                        *** TERIMA KASIH ***<br>
                        Barang yang sudah dibeli tidak dapat ditukar/dikembalikan
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showReceiptModal', false)" class="px-4 py-2 text-xs font-semibold text-gray-600">Tutup</button>
                    <button type="button" onclick="window.print()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-1.5">
                        <x-heroicon-o-printer class="w-4 h-4"/>
                        Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DAFTAR HOLD BILLS -->
    @if($showHoldBillsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Tagihan Tertunda (Hold Bills)</h3>
                    <button type="button" wire:click="$set('showHoldBillsModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse(\App\Models\Sale::where('branch_id', $branch_id)->where('status', \App\Enums\SaleStatus::Suspended)->latest()->get() as $hld)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/70 dark:bg-gray-700/30">
                            <div>
                                <span class="font-bold text-sm text-gray-900 dark:text-white block">{{ $hld->hold_label }}</span>
                                <span class="text-xs text-gray-500 font-mono">{{ $hld->invoice_number }} &bull; {{ $hld->created_at->format('H:i') }}</span>
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 block mt-1">Rp {{ number_format($hld->grand_total, 0, ',', '.') }}</span>
                            </div>
                            <button 
                                type="button" 
                                wire:click="resumeSuspendedBill({{ $hld->id }})" 
                                class="px-3.5 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm"
                            >
                                Buka Tagihan
                            </button>
                        </div>
                    @empty
                        <p class="text-center py-8 text-gray-400 text-xs">Tidak ada transaksi yang di-hold saat ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL ANTREAN PKB SIAP BAYAR -->
    @if($showWorkOrdersModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Antrean Servis Selesai (Siap Bayar)</h3>
                    <button type="button" wire:click="$set('showWorkOrdersModal', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse(\App\Models\WorkOrder::where('branch_id', $branch_id)->where('status', \App\Enums\WorkOrderStatus::ReadyForPayment)->latest()->get() as $rwo)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50/70 dark:bg-gray-700/30">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="plate-badge">{{ $rwo->vehicle->plate_number }}</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $rwo->vehicle->motorModel->name }}</span>
                                </div>
                                <span class="text-xs text-gray-500 block mt-1">Pemilik: {{ $rwo->customer->name ?? '-' }}</span>
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 block mt-0.5">
                                    Total: Rp {{ number_format($rwo->calculateTotal(), 0, ',', '.') }}
                                </span>
                            </div>
                            <button 
                                type="button" 
                                wire:click="pullWorkOrderToCart({{ $rwo->id }})" 
                                class="px-3.5 py-1.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-sm flex items-center gap-1"
                            >
                                <x-heroicon-o-arrow-right class="w-4 h-4"/>
                                Tarik ke POS
                            </button>
                        </div>
                    @empty
                        <p class="text-center py-8 text-gray-400 text-xs">Tidak ada motor yang menunggu pembayaran.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL BUKA REGISTER SHIFT -->
    @if($showOpenRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Buka Shift Kasir (Cash Register)</h3>
                <p class="text-xs text-gray-500">Masukkan nominal uang modal awal yang ada di laci kasir.</p>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Modal Awal Laci (Rp)</label>
                    <input 
                        type="number" 
                        wire:model="opening_balance_input" 
                        class="w-full text-lg font-black rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500"
                        placeholder="Contoh: 200000"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="openRegisterSubmit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md">
                        Buka Shift & Masuk POS
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL TUTUP REGISTER SHIFT -->
    @if($showCloseRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Tutup Shift Kasir (Rekonsiliasi)</h3>
                <p class="text-xs text-gray-500">Hitung total uang fisik tunai di laci kasir saat ini sebelum mengakhiri shift.</p>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Total Uang Fisik di Laci (Rp)</label>
                    <input 
                        type="number" 
                        wire:model="closing_balance_input" 
                        class="w-full text-lg font-black rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500"
                        placeholder="Total uang fisik..."
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCloseRegisterModal', false)" class="px-4 py-2 text-xs font-semibold text-gray-600">Batal</button>
                    <button type="button" wire:click="closeRegisterSubmit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md">
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
