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
            <a 
                href="{{ url('/admin/pos-terminal') }}"
                class="group relative flex flex-col p-4 rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/40 dark:to-blue-950/30 border border-indigo-200/80 dark:border-indigo-800/60 hover:shadow-lg hover:shadow-indigo-500/10 hover:border-indigo-400 transition-all duration-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-500/30 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-calculator class="w-5 h-5"/>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-600 text-white shadow-sm">KASIR</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Terminal POS</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Scan & Transaksi Kasir</span>
            </a>

            <!-- 2. Buat Tiket PKB Baru -->
            <a 
                href="{{ url('/admin/work-orders/create') }}"
                class="group relative flex flex-col p-4 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/30 border border-amber-200/80 dark:border-amber-800/60 hover:shadow-lg hover:shadow-amber-500/10 hover:border-amber-400 transition-all duration-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/30 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5"/>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500 text-white shadow-sm">+ BARU</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Buat Tiket PKB</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Penerimaan Unit Servis</span>
            </a>

            <!-- 3. Scan Barcode Opname -->
            <a 
                href="{{ url('/admin/barcode-stock-opname') }}"
                class="group relative flex flex-col p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/30 border border-emerald-200/80 dark:border-emerald-800/60 hover:shadow-lg hover:shadow-emerald-500/10 hover:border-emerald-400 transition-all duration-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-qr-code class="w-5 h-5"/>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-600 text-white shadow-sm">GUDANG</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Scan Opname</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Audit Barcode Fisik</span>
            </a>

            <!-- 4. Mutasi Antar Cabang -->
            <a 
                href="{{ url('/admin/stock-transfers/create') }}"
                class="group relative flex flex-col p-4 rounded-xl bg-gradient-to-br from-sky-50 to-cyan-50 dark:from-sky-950/40 dark:to-cyan-950/30 border border-sky-200/80 dark:border-sky-800/60 hover:shadow-lg hover:shadow-sky-500/10 hover:border-sky-400 transition-all duration-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-600 text-white flex items-center justify-center shadow-md shadow-sky-500/30 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-truck class="w-5 h-5"/>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-sky-600 text-white shadow-sm">TRANSFER</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">Kirim Mutasi</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Surat Jalan Antar Cabang</span>
            </a>

            <!-- 5. Laporan Laba Rugi -->
            <a 
                href="{{ url('/admin/profit-loss-report') }}"
                class="group relative flex flex-col p-4 rounded-xl bg-gradient-to-br from-purple-50 to-fuchsia-50 dark:from-purple-950/40 dark:to-fuchsia-950/30 border border-purple-200/80 dark:border-purple-800/60 hover:shadow-lg hover:shadow-purple-500/10 hover:border-purple-400 transition-all duration-200 col-span-2 sm:col-span-1"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-600 text-white flex items-center justify-center shadow-md shadow-purple-500/30 group-hover:scale-110 transition-transform">
                        <x-heroicon-o-chart-pie class="w-5 h-5"/>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-600 text-white shadow-sm">P&L</span>
                </div>
                <span class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Laba Rugi Real-Time</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">P&L & Margin Cabang</span>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
