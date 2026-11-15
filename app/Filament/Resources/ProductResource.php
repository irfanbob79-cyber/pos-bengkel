<?php

namespace App\Filament\Resources;

use App\Enums\QualityType;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\BranchStocksRelationManager;
use App\Models\PriceTier;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Katalog & Harga';

    protected static ?string $navigationLabel = 'Katalog Sparepart';

    protected static ?string $modelLabel = 'Sparepart';

    protected static ?string $pluralModelLabel = 'Katalog Sparepart';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Katalog Detail')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Identitas Produk')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->label('Kode SKU')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(60)
                                            ->placeholder('Contoh: AHM-MPX2-08L')
                                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                                        Forms\Components\TextInput::make('barcode')
                                            ->label('Kode Barcode')
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(60)
                                            ->placeholder('Scan barcode atau klik Generate')
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('generateBarcode')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->tooltip('Buat Barcode Internal Otomatis')
                                                    ->action(function (Forms\Set $set) {
                                                        $set('barcode', '899' . mt_rand(1000000000, 9999999999));
                                                    })
                                            ),
                                        Forms\Components\Select::make('quality_type')
                                            ->label('Kualitas Part')
                                            ->options(collect(QualityType::cases())->mapWithKeys(fn (QualityType $q) => [$q->value => $q->label()]))
                                            ->required()
                                            ->default(QualityType::Aftermarket->value),
                                    ]),

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Sparepart / Produk')
                                    ->required()
                                    ->maxLength(200)
                                    ->placeholder('Contoh: Kampas Rem Depan Vario 125/150 Aspira')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Select::make('category_id')
                                            ->label('Kategori')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')->required(),
                                                Forms\Components\TextInput::make('code'),
                                            ]),
                                        Forms\Components\Select::make('brand_id')
                                            ->label('Merek / Brand')
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')->required(),
                                            ]),
                                        Forms\Components\Select::make('unit')
                                            ->label('Satuan')
                                            ->options([
                                                'Pcs' => 'Pcs',
                                                'Set' => 'Set',
                                                'Botol' => 'Botol',
                                                'Liter' => 'Liter',
                                                'Dus' => 'Dus',
                                                'Roll' => 'Roll',
                                                'Meter' => 'Meter',
                                            ])
                                            ->default('Pcs')
                                            ->required(),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('moving_avg_cost')
                                            ->label('HPP Modal Rata-Rata (Moving Average)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(0)
                                            ->helperText('Diperbarui secara otomatis setiap kali ada penerimaan barang pembelian (PO).'),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Aktif')
                                            ->default(true),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Multi-Harga Dinamis (Price Tiers)')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Placeholder::make('info_harga')
                                    ->label('')
                                    ->content('Tentukan harga jual untuk masing-masing tingkatan harga (Eceran, Grosir, Spesial Grosir, atau custom tier). Kasir POS dapat beralih tier harga secara instan.'),
                                Forms\Components\Repeater::make('prices')
                                    ->relationship('prices')
                                    ->schema([
                                        Forms\Components\Select::make('price_tier_id')
                                            ->label('Tingkatan Harga')
                                            ->options(PriceTier::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->distinct(),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Nominal Harga Jual')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp'),
                                        Forms\Components\TextInput::make('min_qty')
                                            ->label('Min. Kuantitas')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->helperText('Qty minimum agar harga tier ini berlaku.'),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(1)
                                    ->addActionLabel('+ Tambah Tingkatan Harga'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Kompatibilitas Motor')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\Select::make('motorModels')
                                    ->label('Pilih Tipe Motor yang Kompatibel')
                                    ->relationship('motorModels', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih satu atau lebih model motor agar mekanik dan kasir mudah mencari kecocokan sparepart.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): string => ($record->brand?->name ?? '-') . ' | ' . ($record->category?->name ?? '-')),
                Tables\Columns\TextColumn::make('quality_type')
                    ->label('Kualitas')
                    ->badge()
                    ->color(fn (QualityType $state): string => match ($state) {
                        QualityType::Oem => 'success',
                        QualityType::Aftermarket => 'info',
                        QualityType::Local => 'warning',
                    })
                    ->formatStateUsing(fn (QualityType $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('moving_avg_cost')
                    ->label('HPP Modal')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('default_price')
                    ->label('Harga Eceran')
                    ->money('IDR', locale: 'id')
                    ->state(fn (Product $record): float => $record->getPriceForTier(null, 1)),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->state(fn (Product $record): float => (float) $record->branchStocks()->sum('qty'))
                    ->badge()
                    ->color(fn (float $state): string => $state <= 10 ? 'danger' : 'success')
                    ->suffix(fn (Product $record): string => ' ' . $record->unit),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Merek')
                    ->relationship('brand', 'name'),
                Tables\Filters\SelectFilter::make('quality_type')
                    ->label('Kualitas')
                    ->options(collect(QualityType::cases())->mapWithKeys(fn (QualityType $q) => [$q->value => $q->label()])),
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
            BranchStocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
