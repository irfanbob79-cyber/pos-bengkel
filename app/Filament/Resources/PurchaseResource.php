<?php

namespace App\Filament\Resources;

use App\Enums\PaymentType;
use App\Enums\PurchaseStatus;
use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Keuangan & Pembelian';

    protected static ?string $navigationLabel = 'Pembelian (Purchase Order)';

    protected static ?string $modelLabel = 'Purchase Order';

    protected static ?string $pluralModelLabel = 'Pembelian (PO)';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pesanan Pembelian')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_number')
                            ->label('Nomor PO')
                            ->default(fn () => 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->required()
                            ->readOnly(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Gudang / Cabang Tujuan')
                            ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Auth::user()?->branch_id ?? 1)
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Pemasok / Supplier')
                            ->options(Supplier::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('payment_type')
                            ->label('Sistem Pembayaran')
                            ->options(collect(PaymentType::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                            ->default(PaymentType::Cash->value)
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo Pembayaran')
                            ->visible(fn (Get $get) => $get('payment_type') === PaymentType::Tempo->value)
                            ->required(fn (Get $get) => $get('payment_type') === PaymentType::Tempo->value),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan PO')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Rincian Sparepart yang Dipesan')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Pilih Sparepart')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(function (?int $state, Set $set, Get $get) {
                                        if ($state) {
                                            $prod = Product::find($state);
                                            if ($prod) {
                                                $cost = (float) $prod->moving_avg_cost;
                                                $qty = (float) ($get('qty') ?? 1);
                                                $set('cost_price', $cost);
                                                $set('subtotal', $cost * $qty);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('qty')
                                    ->label('Jumlah (Qty)')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?float $state, Set $set, Get $get) {
                                        $qty = (float) ($state ?? 1);
                                        $cost = (float) ($get('cost_price') ?? 0);
                                        $set('subtotal', $qty * $cost);
                                    }),
                                Forms\Components\TextInput::make('cost_price')
                                    ->label('Harga Modal Satuan (HPP)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?float $state, Set $set, Get $get) {
                                        $cost = (float) ($state ?? 0);
                                        $qty = (float) ($get('qty') ?? 1);
                                        $set('subtotal', $qty * $cost);
                                    }),
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly(),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Item Pembelian'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('No. PO')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang Tujuan')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipe Bayar')
                    ->formatStateUsing(fn (PaymentType $state): string => $state->label())
                    ->badge()
                    ->color(fn (PaymentType $state): string => $state === PaymentType::Cash ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Nilai PO')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (PurchaseStatus $state): string => match ($state) {
                        PurchaseStatus::Ordered => 'warning',
                        PurchaseStatus::Received => 'success',
                        PurchaseStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (PurchaseStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Waktu Diterima')
                    ->dateTime('d M Y H:i')
                    ->default('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PurchaseStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label('Terima di Gudang')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Purchase $record): bool => $record->status === PurchaseStatus::Ordered)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang Masuk')
                    ->modalDescription('Stok cabang tujuan akan otomatis bertambah dan HPP barang akan diperbarui secara Moving Average.')
                    ->action(function (Purchase $record) {
                        try {
                            app(PurchaseService::class)->receivePurchase($record->id, Auth::id());
                            Notification::make()
                                ->title('Barang Berhasil Diterima!')
                                ->body("Stok dan HPP produk telah disesuaikan.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal menerima PO!')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn (Purchase $record): bool => $record->status === PurchaseStatus::Ordered),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
