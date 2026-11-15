<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Panel Form Pencarian / Scan -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <form wire:submit.prevent="performScan" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Cabang</label>
                        <select wire:model.live="branch_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach(\App\Models\Branch::where('is_active', true)->get() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <div class="relative flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tembak Barcode / Input SKU</label>
                            <input 
                                type="text" 
                                id="scannerInput" 
                                wire:model="search_barcode" 
                                placeholder="Arahkan barcode scanner ke sini lalu tekan Enter..."
                                autofocus
                                class="w-full px-4 py-2.5 rounded-lg border-2 border-primary-500 dark:border-primary-400 dark:bg-gray-700 dark:text-white font-mono text-base focus:ring-2 focus:ring-primary-400 focus:outline-none"
                            />
                        </div>
                        <div class="self-end">
                            <button type="submit" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg shadow transition flex items-center gap-2">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5"/>
                                Cek Stok
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Hasil Pemindaian / Info Produk -->
        @if($productResult)
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                {{ $productResult['quality_type'] }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $productResult['brand'] }} &bull; {{ $productResult['category'] }}
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $productResult['name'] }}
                        </h2>
                        <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">
                            SKU: <span class="font-bold text-gray-700 dark:text-gray-200">{{ $productResult['sku'] }}</span> &bull; 
                            Barcode: <span class="font-bold text-gray-700 dark:text-gray-200">{{ $productResult['barcode'] ?? '-' }}</span>
                        </p>
                    </div>

                    <!-- Indikator Stok & Lokasi Rak -->
                    <div class="mt-4 md:mt-0 flex gap-4">
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Lokasi Rak</span>
                            <span class="text-lg font-bold font-mono text-primary-600 dark:text-primary-400">
                                {{ $productResult['bin_location'] }}
                            </span>
                        </div>
                        <div class="text-center p-3 rounded-lg {{ $productResult['is_low_stock'] ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800' }} border">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Stok Cabang</span>
                            <span class="text-2xl font-black {{ $productResult['is_low_stock'] ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $productResult['current_stock'] }} <span class="text-sm font-normal text-gray-500">{{ $productResult['unit'] }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Daftar Harga per Tingkatan (Price Tiers) -->
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <x-heroicon-o-currency-dollar class="w-5 h-5 text-primary-500"/>
                        Daftar Harga Jual Berdasarkan Tingkatan (Price Tiers)
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @forelse($productResult['prices'] as $p)
                            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">{{ $p['tier_name'] }} (Min. {{ $p['min_qty'] }} {{ $productResult['unit'] }})</span>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                    Rp {{ number_format($p['price'], 0, ',', '.') }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic">Belum ada tingkatan harga khusus yang dikonfigurasi.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scannerInput = document.getElementById('scannerInput');
            if (scannerInput) {
                scannerInput.focus();
            }

            Livewire.on('scan-completed', () => {
                setTimeout(() => {
                    const input = document.getElementById('scannerInput');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }, 100);
            });
        });
    </script>
</x-filament-panels::page>
