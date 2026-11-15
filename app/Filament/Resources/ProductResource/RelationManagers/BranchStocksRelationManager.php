<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Branch;
use App\Models\BranchStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BranchStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'branchStocks';

    protected static ?string $title = 'Posisi Stok & Rak per Cabang';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::pluck('name', 'id'))
                    ->required()
                    ->disabled(fn (?BranchStock $record) => $record !== null),
                Forms\Components\TextInput::make('qty')
                    ->label('Jumlah Stok Fisik')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('bin_location')
                    ->label('Lokasi Rak (Bin Location)')
                    ->placeholder('Contoh: RAK-A02 / DUS-05')
                    ->maxLength(50),
                Forms\Components\TextInput::make('min_stock')
                    ->label('Batas Minimum Stok (Alert)')
                    ->numeric()
                    ->default(5)
                    ->helperText('Sistem akan memberi peringatan jika stok di bawah batas ini.'),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bin_location')
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bin_location')
                    ->label('Lokasi Rak')
                    ->default('-')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Stok Saat Ini')
                    ->sortable()
                    ->badge()
                    ->color(fn (BranchStock $record): string => $record->isLowStock() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stok Minimum')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (BranchStock $record): string => $record->isLowStock() ? 'Stok Kritis' : 'Aman')
                    ->color(fn (BranchStock $record): string => $record->isLowStock() ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Stok Cabang'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
