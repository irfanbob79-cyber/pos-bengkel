<?php

namespace App\Filament\Resources;

use App\Enums\SaleStatus;
use App\Filament\Resources\SaleResource\Pages;
use App\Models\Branch;
use App\Models\Sale;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Riwayat Penjualan (POS)';

    protected static ?string $modelLabel = 'Transaksi POS';

    protected static ?string $pluralModelLabel = 'Riwayat Penjualan (POS)';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Faktur / Invoice')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->default('Pelanggan Umum')
                    ->description(fn (Sale $record): string => $record->workOrder ? "PKB: {$record->workOrder->wo_number}" : '')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priceTier.name')
                    ->label('Tier Harga')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (SaleStatus $state): string => match ($state) {
                        SaleStatus::Completed => 'success',
                        SaleStatus::Suspended => 'warning',
                        SaleStatus::Void => 'danger',
                    })
                    ->formatStateUsing(fn (SaleStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Transaksi')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_summary')
                    ->label('Metode Bayar')
                    ->badge()
                    ->color('info')
                    ->state(fn (Sale $record): string => $record->payments->map(fn ($p) => $p->payment_method->label())->join(', ')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Transaksi')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(SaleStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
        ];
    }
}
