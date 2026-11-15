<?php

namespace App\Filament\Resources;

use App\Enums\CashRegisterStatus;
use App\Filament\Resources\CashRegisterResource\Pages;
use App\Models\CashRegister;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Pengaturan & Cabang';

    protected static ?string $navigationLabel = 'Shift Kasir (Cash Register)';

    protected static ?string $modelLabel = 'Shift Kasir';

    protected static ?string $pluralModelLabel = 'Shift Kasir (Cash Register)';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Kasir')
                    ->sortable(),
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Buka Shift')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Modal Awal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closing_balance')
                    ->label('Uang Fisik Laci')
                    ->money('IDR', locale: 'id')
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Selisih (+/-)')
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state == 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?float $state): string => $state !== null ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (CashRegisterStatus $state): string => match ($state) {
                        CashRegisterStatus::Open => 'warning',
                        CashRegisterStatus::Closed => 'success',
                    })
                    ->formatStateUsing(fn (CashRegisterStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Tutup Shift')
                    ->dateTime('d M Y H:i')
                    ->default('-')
                    ->sortable(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CashRegisterStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashRegisters::route('/'),
        ];
    }
}
