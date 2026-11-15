<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Riwayat Servis Lintas Cabang';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('wo_number')
            ->columns([
                Tables\Columns\TextColumn::make('wo_number')
                    ->label('No. PKB')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang Servis')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_km')
                    ->label('Kilometer (KM)')
                    ->numeric()
                    ->suffix(' KM')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik')
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (WorkOrderStatus $state): string => match ($state) {
                        WorkOrderStatus::Queued => 'gray',
                        WorkOrderStatus::InProgress => 'warning',
                        WorkOrderStatus::ReadyForPayment => 'info',
                        WorkOrderStatus::Completed => 'success',
                        WorkOrderStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (WorkOrderStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Biaya')
                    ->money('IDR', locale: 'id')
                    ->state(fn (WorkOrder $record): float => $record->calculateTotal()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Servis')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
