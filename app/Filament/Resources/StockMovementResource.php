<?php

namespace App\Filament\Resources;

use App\Enums\StockMovementType;
use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\Branch;
use App\Models\StockMovement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Inventori & Gudang';

    protected static ?string $navigationLabel = 'Kartu Stok (Audit Trail)';

    protected static ?string $modelLabel = 'Riwayat Mutasi';

    protected static ?string $pluralModelLabel = 'Kartu Stok (Audit Trail)';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Mutasi')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Sparepart')
                    ->description(fn (StockMovement $record): string => "SKU: {$record->product?->sku} | Barcode: {$record->product?->barcode}")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis Aktivitas')
                    ->badge()
                    ->color(fn (StockMovementType $state): string => match ($state) {
                        StockMovementType::PurchaseIn => 'success',
                        StockMovementType::SaleOut => 'danger',
                        StockMovementType::TransferIn => 'info',
                        StockMovementType::TransferOut => 'warning',
                        StockMovementType::OpnameAdjustment => 'primary',
                        StockMovementType::InitialImport => 'gray',
                    })
                    ->formatStateUsing(fn (StockMovementType $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_change')
                    ->label('Perubahan (+/-)')
                    ->badge()
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (float $state): string => ($state > 0 ? "+{$state}" : (string) $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Saldo Akhir')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Eksekutor')
                    ->default('Sistem')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn (StockMovement $record): ?string => $record->notes),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Mutasi')
                    ->options(collect(StockMovementType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
