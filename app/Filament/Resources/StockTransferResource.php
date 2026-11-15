<?php

namespace App\Filament\Resources;

use App\Enums\StockTransferStatus;
use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Manajemen Inventori & Gudang';

    protected static ?string $navigationLabel = 'Mutasi Stok Antar Cabang';

    protected static ?string $modelLabel = 'Mutasi Stok';

    protected static ?string $pluralModelLabel = 'Mutasi Stok Antar Cabang';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['draft', 'pending', 'in_transit'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengiriman Mutasi')
                    ->schema([
                        Forms\Components\TextInput::make('transfer_number')
                            ->label('No. Surat Jalan / Transfer')
                            ->default(fn () => 'TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->required()
                            ->readOnly(),
                        Forms\Components\Select::make('from_branch_id')
                            ->label('Cabang Asal (Pengirim)')
                            ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->disabled(fn (?StockTransfer $record) => $record && $record->status !== StockTransferStatus::Draft),
                        Forms\Components\Select::make('to_branch_id')
                            ->label('Cabang Tujuan (Penerima)')
                            ->options(function (Get $get) {
                                $fromId = $get('from_branch_id');

                                return Branch::where('is_active', true)
                                    ->when($fromId, fn ($q) => $q->where('id', '!=', $fromId))
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->disabled(fn (?StockTransfer $record) => $record && $record->status !== StockTransferStatus::Draft),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Mutasi / Sopir / Ekspedisi')
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Rincian Sparepart yang Dimutasi')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Pilih Sparepart')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('qty_sent')
                                    ->label('Qty Dikirim')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1),
                                Forms\Components\TextInput::make('qty_received')
                                    ->label('Qty Diterima')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(fn (?StockTransfer $record) => ! $record || $record->status === StockTransferStatus::Draft),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Item Mutasi')
                            ->disabled(fn (?StockTransfer $record) => $record && $record->status !== StockTransferStatus::Draft),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('No. Transfer')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('fromBranch.name')
                    ->label('Dari Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('toBranch.name')
                    ->label('Ke Cabang')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StockTransferStatus $state): string => match ($state) {
                        StockTransferStatus::Draft => 'gray',
                        StockTransferStatus::InTransit => 'warning',
                        StockTransferStatus::Completed => 'success',
                        StockTransferStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (StockTransferStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Item')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Buat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(StockTransferStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('from_branch_id')
                    ->label('Cabang Asal')
                    ->relationship('fromBranch', 'name'),
                Tables\Filters\SelectFilter::make('to_branch_id')
                    ->label('Cabang Tujuan')
                    ->relationship('toBranch', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('dispatch')
                    ->label('Kirim Barang')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengiriman Barang')
                    ->modalDescription('Stok di cabang pengirim akan otomatis dikurangi secara atomik.')
                    ->visible(fn (StockTransfer $record): bool => $record->status === StockTransferStatus::Draft)
                    ->action(function (StockTransfer $record) {
                        try {
                            app(StockService::class)->dispatchTransfer($record, Auth::id());
                            Notification::make()
                                ->title('Barang berhasil dikirim!')
                                ->body("Status mutasi {$record->transfer_number} kini In-Transit.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mengirim transfer!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('receive')
                    ->label('Konfirmasi Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockTransfer $record): bool => $record->status === StockTransferStatus::InTransit)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Barang')
                    ->modalDescription('Stok di cabang tujuan akan otomatis bertambah sesuai barang yang datang.')
                    ->action(function (StockTransfer $record) {
                        try {
                            $quantities = [];
                            foreach ($record->items as $item) {
                                $quantities[$item->id] = (float) $item->qty_sent;
                            }
                            app(StockService::class)->receiveTransfer($record, $quantities, Auth::id() ?? 1);
                            Notification::make()
                                ->title('Mutasi Selesai!')
                                ->body("Stok pada cabang {$record->toBranch->name} telah diperbarui.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal memproses penerimaan!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->visible(fn (StockTransfer $record): bool => $record->status === StockTransferStatus::Draft),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
