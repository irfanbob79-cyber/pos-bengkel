<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Services\ProfitLossService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ProfitLossReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Keuangan & Pembelian';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi (P&L)';

    protected static ?string $title = 'Laporan Laba Rugi Real-Time (P&L)';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.profit-loss-report';

    public ?int $branch_id = null;
    public string $period_preset = 'this_month'; // 'today', 'this_month', 'last_month', 'this_year', 'custom'
    public string $start_date = '';
    public string $end_date = '';

    public array $report = [];

    public function mount(): void
    {
        $user = Auth::user();
        // Super Admin default to null (konsolidasi), branch manager defaults to their branch
        $this->branch_id = $user->branch_id;

        $this->applyPreset('this_month');
    }

    public function applyPreset(string $preset): void
    {
        $this->period_preset = $preset;

        switch ($preset) {
            case 'today':
                $this->start_date = Carbon::today()->toDateString();
                $this->end_date = Carbon::today()->toDateString();
                break;
            case 'last_month':
                $this->start_date = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                $this->end_date = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $this->start_date = Carbon::now()->startOfYear()->toDateString();
                $this->end_date = Carbon::now()->endOfYear()->toDateString();
                break;
            case 'this_month':
            default:
                $this->start_date = Carbon::now()->startOfMonth()->toDateString();
                $this->end_date = Carbon::now()->endOfMonth()->toDateString();
                break;
        }

        $this->calculateReport();
    }

    public function updatedBranchId(): void
    {
        $this->calculateReport();
    }

    public function updatedStartDate(): void
    {
        $this->period_preset = 'custom';
        $this->calculateReport();
    }

    public function updatedEndDate(): void
    {
        $this->period_preset = 'custom';
        $this->calculateReport();
    }

    public function calculateReport(): void
    {
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->endOfDay();

        $service = app(ProfitLossService::class);
        $this->report = $service->calculate(
            branchId: $this->branch_id ?: null,
            startDate: $start,
            endDate: $end
        );
    }
}
