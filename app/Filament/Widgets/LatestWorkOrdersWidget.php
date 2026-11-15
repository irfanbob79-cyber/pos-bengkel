<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class LatestWorkOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Antrean & Pengerjaan Servis Terkini (Live PKB Tracker)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()
                    ->with(['vehicle.motorModel', 'customer', 'mechanic', 'branch'])
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('wo_number')
                    ->label('No. PKB')
                    ->searchable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Plat Nomor')
                    ->formatStateUsing(fn ($state) => new HtmlString("<span class='plate-badge'>" . e($state ?? '-') . "</span>"))
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan & Motor')
                    ->description(fn (WorkOrder $record): string => $record->vehicle?->motorModel?->name ?? 'Motor Umum')
                    ->searchable(),

                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-wrench')
                    ->placeholder('Belum ditugaskan'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Servis')
                    ->badge()
                    ->formatStateUsing(fn (WorkOrderStatus $state): string => $state->label())
                    ->color(fn (WorkOrderStatus $state): string => match ($state) {
                        WorkOrderStatus::Queued => 'gray',
                        WorkOrderStatus::InProgress => 'warning',
                        WorkOrderStatus::ReadyForPayment => 'success',
                        WorkOrderStatus::Completed => 'info',
                        WorkOrderStatus::Cancelled => 'danger',
                    }),

                Tables\Columns\TextColumn::make('total_estimated')
                    ->label('Estimasi Biaya')
                    ->money('IDR', locale: 'id')
                    ->alignment('right'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Masuk')
                    ->since()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Buka PKB')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (WorkOrder $record): string => url("/admin/work-orders/{$record->id}/edit"))
                    ->button()
                    ->size('xs')
                    ->color('primary'),
            ])
            ->paginated(false);
    }
}
