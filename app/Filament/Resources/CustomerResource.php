<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Models\PriceTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Pelanggan & Pemilik';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan & Pemilik';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pelanggan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Pemilik / Pelanggan')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Contoh: Budi Santoso / Bengkel Maju'),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor WhatsApp / HP')
                            ->tel()
                            ->maxLength(30)
                            ->placeholder('0812-xxxx-xxxx')
                            ->helperText('Digunakan untuk kirim reminder servis berkala & kwitansi WhatsApp.'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),
                        Forms\Components\Select::make('price_tier_id')
                            ->label('Tingkatan Harga Khusus (Price Tier)')
                            ->options(PriceTier::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => PriceTier::where('is_default', true)->value('id'))
                            ->helperText('Jika pelanggan ini adalah bengkel rekanan/grosir, tentukan tier harga default-nya.'),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->url(fn (Customer $record): ?string => $record->phone ? "https://wa.me/" . preg_replace('/[^0-9]/', '', $record->phone) : null, shouldOpenInNewTab: true),
                Tables\Columns\TextColumn::make('priceTier.name')
                    ->label('Tingkatan Harga')
                    ->badge()
                    ->color('primary')
                    ->default('Eceran Standar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicles_count')
                    ->label('Motor Terdaftar')
                    ->counts('vehicles')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('work_orders_count')
                    ->label('Total Kunjungan Servis')
                    ->counts('workOrders')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('price_tier_id')
                    ->label('Tingkatan Harga')
                    ->relationship('priceTier', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
