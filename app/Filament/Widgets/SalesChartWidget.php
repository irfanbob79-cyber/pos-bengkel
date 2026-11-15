<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Pendapatan Penjualan (7 Hari)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 2,
    ];

    protected function getData(): array
    {
        $days = [];
        $sales = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->translatedFormat('D, d M');
            $sales[] = (float) Sale::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('final_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Omzet (Rp)',
                    'data' => $sales,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $days,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
