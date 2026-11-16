<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-sm font-bold tracking-tight text-gray-950 dark:text-white">Pusat Aksi Cepat Bengkel (Fast Shortcuts)</span>
                </div>
                <span class="text-xs text-gray-400 hidden sm:inline">Akses kilat operasional harian kasir & mekanik</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <!-- 1. Buka Terminal POS -->
            <a href="{{ url('/admin/pos-terminal') }}" class="qa-card group">
                <div class="flex items-center justify-between mb-3">
                    <div class="qa-icon-indigo group-hover:scale-105 transition-transform">
                        <x-heroicon-o-calculator class="w-5 h-5"/>
                    </div>
                    <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; background-color: #4f46e5; color: #ffffff;">KASIR</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white">Terminal POS</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Scan & Transaksi Kasir</span>
            </a>

            <!-- 2. Buat Tiket PKB Baru -->
            <a href="{{ url('/admin/work-orders/create') }}" class="qa-card group">
                <div class="flex items-center justify-between mb-3">
                    <div class="qa-icon-amber group-hover:scale-105 transition-transform">
                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5"/>
                    </div>
                    <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; background-color: #f59e0b; color: #ffffff;">+ BARU</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white">Buat Tiket PKB</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Penerimaan Unit Servis</span>
            </a>

            <!-- 3. Scan Barcode Opname -->
            <a href="{{ url('/admin/barcode-stock-opname') }}" class="qa-card group">
                <div class="flex items-center justify-between mb-3">
                    <div class="qa-icon-emerald group-hover:scale-105 transition-transform">
                        <x-heroicon-o-qr-code class="w-5 h-5"/>
                    </div>
                    <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; background-color: #10b981; color: #ffffff;">GUDANG</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white">Scan Opname</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Audit Barcode Fisik</span>
            </a>

            <!-- 4. Mutasi Antar Cabang -->
            <a href="{{ url('/admin/stock-transfers/create') }}" class="qa-card group">
                <div class="flex items-center justify-between mb-3">
                    <div class="qa-icon-sky group-hover:scale-105 transition-transform">
                        <x-heroicon-o-truck class="w-5 h-5"/>
                    </div>
                    <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; background-color: #0284c7; color: #ffffff;">MUTASI</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white">Kirim Mutasi</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Surat Jalan Antar Cabang</span>
            </a>

            <!-- 5. Laporan Laba Rugi -->
            <a href="{{ url('/admin/profit-loss-report') }}" class="qa-card group col-span-2 sm:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <div class="qa-icon-purple group-hover:scale-105 transition-transform">
                        <x-heroicon-o-chart-pie class="w-5 h-5"/>
                    </div>
                    <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 4px; background-color: #9333ea; color: #ffffff;">P&L</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white">Laba Rugi Real-Time</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">P&L & Margin Cabang</span>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
