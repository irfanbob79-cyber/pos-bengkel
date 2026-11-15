<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceTierResource\Pages;
use App\Models\PriceTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PriceTierResource extends Resource
{
    protected static ?string $model = PriceTier::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Master Katalog & Harga';

    protected static ?string $navigationLabel = 'Tingkatan Harga (Multi-Tier)';

    protected static ?string $modelLabel = 'Tingkatan Harga';

    protected static ?string $pluralModelLabel = 'Tingkatan Harga';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tingkatan Harga')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Tier')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('Contoh: VIP_BENGKEL / OJOL')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tingkatan Harga')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Harga Khusus Bengkel Rekanan'),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Jadikan Harga Default (Eceran)')
                            ->helperText('Hanya ada satu harga default utama dalam sistem.')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Tier')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tingkatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default Kasir')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_prices_count')
                    ->label('Produk Dikonfigurasi')
                    ->counts('productPrices')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceTiers::route('/'),
            'create' => Pages\CreatePriceTier::route('/create'),
            'edit' => Pages\EditPriceTier::route('/{record}/edit'),
        ];
    }
}
