<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
use App\Models\BranchStock;
use App\Models\Sale;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // 1. Total Penjualan Hari Ini
        $todaySales = Sale::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('grand_total');

        $yesterdaySales = Sale::whereDate('created_at', $yesterday)
            ->where('status', 'completed')
            ->sum('grand_total');

        $growth = $yesterdaySales > 0 
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1) 
            : 0;

        $growthText = $growth >= 0 ? "+{$growth}% dari kemarin" : "{$growth}% dari kemarin";

        // 7-day sparkline
        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $sparkline[] = (float) Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('grand_total');
        }

        // 2. PKB Aktif (Queued, In Progress, Ready for Payment)
        $activeWorkOrders = WorkOrder::whereIn('status', [
            WorkOrderStatus::Queued->value,
            WorkOrderStatus::InProgress->value,
            WorkOrderStatus::ReadyForPayment->value,
        ])->count();

        $inProgressCount = WorkOrder::where('status', WorkOrderStatus::InProgress->value)->count();

        // 3. Stok Menipis / Kritis
        $lowStockCount = BranchStock::whereColumn('qty', '<=', 'min_stock')->count();

        // 4. Kendaraan Masuk Hari Ini
        $todayVehicles = WorkOrder::whereDate('created_at', $today)->count();

        return [
            Stat::make('Omzet Penjualan Hari Ini', 'Rp ' . number_format($todaySales, 0, ',', '.'))
                ->description($growthText)
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($sparkline)
                ->color($growth >= 0 ? 'success' : 'danger'),

            Stat::make('PKB Aktif & Antrean', $activeWorkOrders . ' Unit')
                ->description("{$inProgressCount} unit sedang dikerjakan mekanik")
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),

            Stat::make('Peringatan Stok Menipis', $lowStockCount . ' Produk')
                ->description($lowStockCount > 0 ? 'Segera lakukan Purchase Order (PO)' : 'Stok semua cabang aman')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Kedatangan Servis Hari Ini', $todayVehicles . ' Motor')
                ->description('Total tiket PKB diterbitkan')
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),
        ];
    }
}
