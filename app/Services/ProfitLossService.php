<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\StockOpnameStatus;
use App\Models\Expense;
use App\Models\SaleItem;
use App\Models\StockOpnameItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    /**
     * Hitung Laporan Profit & Loss (P&L) Real-time.
     */
    public function calculate(
        ?int $branchId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        // 1. Query Item Terjual pada Transaksi Completed
        $salesQuery = SaleItem::whereHas('sale', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('status', SaleStatus::Completed)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });

        // Omzet Sparepart
        $partsRevenue = (float) (clone $salesQuery)->where('item_type', 'product')->sum('subtotal');

        // Omzet Jasa Servis
        $servicesRevenue = (float) (clone $salesQuery)->where('item_type', 'service')->sum('subtotal');

        // Total Pendapatan Kotor
        $totalRevenue = $partsRevenue + $servicesRevenue;

        // Total HPP (Cost of Goods Sold) dari snapshot harga modal saat transaksi
        $totalCogs = (float) (clone $salesQuery)->where('item_type', 'product')
            ->selectRaw('SUM(cost_snapshot * qty) as total_cogs')
            ->value('total_cogs') ?? 0.0;

        // Laba Kotor (Gross Profit)
        $grossProfit = $totalRevenue - $totalCogs;

        // 2. Beban Operasional & Komisi Mekanik
        // Komisi Mekanik dari transaksi penjualan
        $totalCommissions = (float) (clone $salesQuery)->sum('mechanic_commission');

        // Beban Operasional Cabang (Listrik, Air, Gaji, dll.)
        $expenseQuery = Expense::whereBetween('expense_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ]);
        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        }
        $branchExpenses = (float) $expenseQuery->sum('amount');

        // Rincian beban per kategori
        $expenseBreakdown = (clone $expenseQuery)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Kerugian Selisih Stock Opname (nilai rupiah dari selisih minus)
        $opnameLossQuery = StockOpnameItem::whereHas('opname', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('status', StockOpnameStatus::Completed)
                ->whereBetween('completed_at', [$startDate, $endDate]);

            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->where('difference_qty', '<', 0);

        $opnameLoss = (float) (clone $opnameLossQuery)
            ->selectRaw('SUM(ABS(difference_qty) * cost_snapshot) as total_loss')
            ->value('total_loss') ?? 0.0;

        // Total Seluruh Beban & Komisi
        $totalExpenses = $totalCommissions + $branchExpenses + $opnameLoss;

        // 3. Laba Bersih (Net Profit)
        $netProfit = $grossProfit - $totalExpenses;

        // Margin Laba Kotor & Bersih (%)
        $grossMarginPct = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;
        $netMarginPct = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'branch_id' => $branchId,
            'revenue' => [
                'parts_revenue' => $partsRevenue,
                'services_revenue' => $servicesRevenue,
                'total_revenue' => $totalRevenue,
            ],
            'cogs' => [
                'total_cogs' => $totalCogs,
            ],
            'gross_profit' => [
                'amount' => $grossProfit,
                'margin_percent' => $grossMarginPct,
            ],
            'expenses' => [
                'mechanic_commissions' => $totalCommissions,
                'branch_expenses' => $branchExpenses,
                'opname_loss' => $opnameLoss,
                'total_expenses' => $totalExpenses,
                'breakdown' => $expenseBreakdown,
            ],
            'net_profit' => [
                'amount' => $netProfit,
                'margin_percent' => $netMarginPct,
            ],
        ];
    }
}
