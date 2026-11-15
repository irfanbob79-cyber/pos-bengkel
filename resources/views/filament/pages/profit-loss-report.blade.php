<x-filament-panels::page>
    <div class="space-y-6">
        <!-- FILTER BAR -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <!-- Cabang Selector -->
                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                        Cakupan Cabang
                    </label>
                    <select wire:model.live="branch_id" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">🏢 Semua Cabang (Konsolidasi Pusat)</option>
                        @foreach(\App\Models\Branch::where('is_active', true)->get() as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Periode Presets -->
                <div class="w-full md:w-auto">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">
                        Pilihan Periode
                    </label>
                    <div class="flex gap-1.5 flex-wrap">
                        <button type="button" wire:click="applyPreset('today')" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period_preset === 'today' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Hari Ini</button>
                        <button type="button" wire:click="applyPreset('this_month')" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period_preset === 'this_month' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Bulan Ini</button>
                        <button type="button" wire:click="applyPreset('last_month')" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period_preset === 'last_month' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Bulan Lalu</button>
                        <button type="button" wire:click="applyPreset('this_year')" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition {{ $period_preset === 'this_year' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Tahun Ini</button>
                    </div>
                </div>

                <!-- Custom Date Inputs -->
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1">Dari Tanggal</label>
                        <input type="date" wire:model.live="start_date" class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white py-1.5">
                    </div>
                    <div>
                        <label class="block text-[11px] text-gray-500 mb-1">Sampai Tanggal</label>
                        <input type="date" wire:model.live="end_date" class="text-xs rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white py-1.5">
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($report))
            <!-- 5 KPI RINGKASAN FINANSIAL -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Pendapatan Kotor -->
                <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Total Pendapatan (Omzet)</span>
                    <span class="text-2xl font-black text-gray-900 dark:text-white mt-1 block">
                        Rp {{ number_format($report['revenue']['total_revenue'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-gray-400 mt-1 block">
                        Part: Rp {{ number_format($report['revenue']['parts_revenue'], 0, ',', '.') }}<br>
                        Jasa: Rp {{ number_format($report['revenue']['services_revenue'], 0, ',', '.') }}
                    </span>
                </div>

                <!-- HPP Modal -->
                <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Harga Pokok (HPP/COGS)</span>
                    <span class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 block">
                        Rp {{ number_format($report['cogs']['total_cogs'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-gray-400 mt-1 block">Modal dasar part terjual</span>
                </div>

                <!-- Laba Kotor -->
                <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Laba Kotor (Gross Profit)</span>
                    <span class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1 block">
                        Rp {{ number_format($report['gross_profit']['amount'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-blue-600 font-semibold mt-1 block">
                        Margin: {{ $report['gross_profit']['margin_percent'] }}%
                    </span>
                </div>

                <!-- Beban & Komisi -->
                <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block">Beban & Komisi</span>
                    <span class="text-2xl font-black text-red-500 mt-1 block">
                        Rp {{ number_format($report['expenses']['total_expenses'], 0, ',', '.') }}
                    </span>
                    <span class="text-[11px] text-gray-400 mt-1 block">
                        Komisi: Rp {{ number_format($report['expenses']['mechanic_commissions'], 0, ',', '.') }}<br>
                        Beban: Rp {{ number_format($report['expenses']['branch_expenses'], 0, ',', '.') }}
                    </span>
                </div>

                <!-- Laba Bersih (Net Profit) -->
                <div class="p-5 {{ $report['net_profit']['amount'] >= 0 ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} rounded-xl shadow-sm border">
                    <span class="text-xs font-semibold {{ $report['net_profit']['amount'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }} uppercase tracking-wider block">
                        Laba Bersih (Net Profit)
                    </span>
                    <span class="text-2xl font-black {{ $report['net_profit']['amount'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} mt-1 block">
                        Rp {{ number_format($report['net_profit']['amount'], 0, ',', '.') }}
                    </span>
                    <span class="text-xs font-bold {{ $report['net_profit']['amount'] >= 0 ? 'text-emerald-700' : 'text-red-700' }} mt-1 block">
                        Net Margin: {{ $report['net_profit']['margin_percent'] }}%
                    </span>
                </div>
            </div>

            <!-- LAPORAN LABA RUGI FORMAL (INCOME STATEMENT) -->
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Laporan Laba Rugi Komprehensif</h3>
                        <p class="text-xs text-gray-500">Periode: {{ date('d F Y', strtotime($report['period']['start_date'])) }} s/d {{ date('d F Y', strtotime($report['period']['end_date'])) }}</p>
                    </div>
                    <button type="button" onclick="window.print()" class="px-4 py-2 text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-white rounded-lg flex items-center gap-1.5">
                        <x-heroicon-o-printer class="w-4 h-4"/>
                        Cetak Laporan
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <!-- PENDAPATAN -->
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-700 dark:text-gray-300 uppercase">
                            <tr>
                                <th class="py-2.5 px-4 font-bold" colspan="2">1. PENDAPATAN OPERASIONAL (REVENUE)</th>
                                <th class="py-2.5 px-4 text-right">JUMLAH (RP)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr>
                                <td class="py-2 px-6" colspan="2">Penjualan Suku Cadang & Sparepart Motor</td>
                                <td class="py-2 px-4 text-right font-medium">Rp {{ number_format($report['revenue']['parts_revenue'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-6" colspan="2">Pendapatan Jasa Servis & Perbaikan Bengkel</td>
                                <td class="py-2 px-4 text-right font-medium">Rp {{ number_format($report['revenue']['services_revenue'], 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-gray-50/50 dark:bg-gray-700/20 font-bold">
                                <td class="py-2.5 px-4 text-primary-600" colspan="2">TOTAL PENDAPATAN KOTOR</td>
                                <td class="py-2.5 px-4 text-right text-primary-600">Rp {{ number_format($report['revenue']['total_revenue'], 0, ',', '.') }}</td>
                            </tr>

                            <!-- HPP -->
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-700 dark:text-gray-300 uppercase font-bold">
                                <td class="py-2.5 px-4" colspan="2">2. HARGA POKOK PENJUALAN (HPP / COGS)</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-6" colspan="2">Harga Pokok Barang Terjual (Snapshot Modal Pembelian)</td>
                                <td class="py-2 px-4 text-right font-medium text-amber-600">(Rp {{ number_format($report['cogs']['total_cogs'], 0, ',', '.') }})</td>
                            </tr>
                            <tr class="bg-blue-50/50 dark:bg-blue-900/10 font-bold text-blue-700 dark:text-blue-300">
                                <td class="py-2.5 px-4" colspan="2">LABA KOTOR (GROSS PROFIT)</td>
                                <td class="py-2.5 px-4 text-right">Rp {{ number_format($report['gross_profit']['amount'], 0, ',', '.') }}</td>
                            </tr>

                            <!-- BEBAN & KOMISI -->
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-700 dark:text-gray-300 uppercase font-bold">
                                <td class="py-2.5 px-4" colspan="2">3. BEBAN OPERASIONAL & KOMISI</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-6" colspan="2">Total Komisi Mekanik Servis</td>
                                <td class="py-2 px-4 text-right font-medium text-red-500">(Rp {{ number_format($report['expenses']['mechanic_commissions'], 0, ',', '.') }})</td>
                            </tr>
                            @foreach($report['expenses']['breakdown'] as $category => $amount)
                                <tr>
                                    <td class="py-2 px-6 text-gray-600 dark:text-gray-400" colspan="2">Biaya: {{ $category }}</td>
                                    <td class="py-2 px-4 text-right font-medium text-red-500">(Rp {{ number_format($amount, 0, ',', '.') }})</td>
                                </tr>
                            @endforeach
                            @if($report['expenses']['opname_loss'] > 0)
                                <tr>
                                    <td class="py-2 px-6 text-gray-600 dark:text-gray-400" colspan="2">Kerugian Selisih Fisik Stock Opname</td>
                                    <td class="py-2 px-4 text-right font-medium text-red-500">(Rp {{ number_format($report['expenses']['opname_loss'], 0, ',', '.') }})</td>
                                </tr>
                            @endif
                            <tr class="bg-gray-50/50 dark:bg-gray-700/20 font-bold">
                                <td class="py-2.5 px-4 text-red-600" colspan="2">TOTAL BEBAN OPERASIONAL</td>
                                <td class="py-2.5 px-4 text-right text-red-600">(Rp {{ number_format($report['expenses']['total_expenses'], 0, ',', '.') }})</td>
                            </tr>

                            <!-- LABA BERSIH -->
                            <tr class="text-base font-black {{ $report['net_profit']['amount'] >= 0 ? 'bg-emerald-100/70 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100/70 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">
                                <td class="py-3.5 px-4 uppercase" colspan="2">LABA BERSIH AKHIR (NET PROFIT)</td>
                                <td class="py-3.5 px-4 text-right">Rp {{ number_format($report['net_profit']['amount'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
