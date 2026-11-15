<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Perintah Kerja Bengkel (PKB)';

    protected static ?string $modelLabel = 'Tiket PKB';

    protected static ?string $pluralModelLabel = 'Perintah Kerja Bengkel (PKB)';

    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', [
            WorkOrderStatus::Queued->value,
            WorkOrderStatus::InProgress->value,
            WorkOrderStatus::ReadyForPayment->value,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Servis & Kendaraan')
                    ->schema([
                        Forms\Components\TextInput::make('wo_number')
                            ->label('Nomor PKB')
                            ->default(fn () => 'WO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->required()
                            ->readOnly(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Cabang Bengkel')
                            ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Auth::user()?->branch_id ?? Branch::first()?->id ?? 1)
                            ->required(),
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Cari Plat Nomor Kendaraan')
                            ->relationship('vehicle', 'plate_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?int $state, Set $set) {
                                if ($state) {
                                    $vehicle = Vehicle::with('customer')->find($state);
                                    if ($vehicle) {
                                        $set('customer_id', $vehicle->customer_id);
                                        $set('current_km', $vehicle->last_km);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('customer_id')
                            ->label('Nama Pemilik')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('service_advisor_id')
                            ->label('Service Advisor (SA)')
                            ->options(User::where('role', UserRole::ServiceAdvisor)->orWhere('role', UserRole::SuperAdmin)->pluck('name', 'id'))
                            ->default(fn () => Auth::id()),
                        Forms\Components\Select::make('mechanic_id')
                            ->label('Mekanik Ditugaskan')
                            ->options(User::where('role', UserRole::Mechanic)->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('current_km')
                            ->label('KM Masuk')
                            ->numeric()
                            ->default(0)
                            ->suffix('KM')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status Pengerjaan')
                            ->options(collect(WorkOrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->default(WorkOrderStatus::Queued->value)
                            ->required(),
                        Forms\Components\Textarea::make('complaints')
                            ->label('Keluhan Pelanggan')
                            ->placeholder('Contoh: Tarikan awal gredek, rem depan bunyi, ganti oli rutin')
                            ->columnSpan(2),
                        Forms\Components\Textarea::make('diagnosis')
                            ->label('Diagnosa & Saran Mekanik')
                            ->placeholder('Contoh: Kampas ganda kotor, roller aus, kampas rem depan sisa 10%')
                            ->columnSpan(2),
                    ])->columns(4),

                Forms\Components\Section::make('Rincian Jasa Servis & Sparepart')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('item_type')
                                    ->label('Jenis Item')
                                    ->options([
                                        'service' => 'Jasa Servis',
                                        'product' => 'Sparepart',
                                    ])
                                    ->default('service')
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('service_id')
                                    ->label('Pilih Jasa Servis')
                                    ->options(Service::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('item_type') === 'service')
                                    ->required(fn (Get $get) => $get('item_type') === 'service')
                                    ->live()
                                    ->afterStateUpdated(function (?int $state, Set $set, Get $get) {
                                        if ($state) {
                                            $service = Service::find($state);
                                            if ($service) {
                                                $price = (float) $service->default_price;
                                                $qty = (float) ($get('qty') ?? 1);
                                                $set('unit_price', $price);
                                                $set('subtotal', $price * $qty);
                                                $set('commission_amount', $service->calculateCommission($price) * $qty);
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('product_id')
                                    ->label('Pilih Sparepart')
                                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get) => $get('item_type') === 'product')
                                    ->required(fn (Get $get) => $get('item_type') === 'product')
                                    ->live()
                                    ->afterStateUpdated(function (?int $state, Set $set, Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $qty = (float) ($get('qty') ?? 1);
                                                $price = $product->getPriceForTier(null, $qty);
                                                $set('unit_price', $price);
                                                $set('subtotal', $price * $qty);
                                                $set('commission_amount', 0);
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('mechanic_id')
                                    ->label('Mekanik')
                                    ->options(User::where('role', UserRole::Mechanic)->pluck('name', 'id'))
                                    ->placeholder('Sesuai PKB'),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?float $state, Set $set, Get $get) {
                                        $qty = (float) ($state ?? 1);
                                        $unitPrice = (float) ($get('unit_price') ?? 0);
                                        $set('subtotal', $unitPrice * $qty);

                                        if ($get('item_type') === 'service' && $get('service_id')) {
                                            $service = Service::find($get('service_id'));
                                            if ($service) {
                                                $set('commission_amount', $service->calculateCommission($unitPrice) * $qty);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?float $state, Set $set, Get $get) {
                                        $unitPrice = (float) ($state ?? 0);
                                        $qty = (float) ($get('qty') ?? 1);
                                        $set('subtotal', $unitPrice * $qty);
                                    }),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly(),

                                Forms\Components\TextInput::make('commission_amount')
                                    ->label('Komisi Mekanik')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->readOnly(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('+ Tambah Part / Jasa'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wo_number')
                    ->label('No. PKB')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Plat Motor')
                    ->badge()
                    ->color('info')
                    ->description(fn (WorkOrder $record): string => $record->vehicle?->motorModel?->name ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pemilik')
                    ->description(fn (WorkOrder $record): string => $record->customer?->phone ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik')
                    ->badge()
                    ->color('gray')
                    ->default('Belum Ditunjuk')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status PKB')
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
                    ->label('Estimasi Biaya')
                    ->money('IDR', locale: 'id')
                    ->state(fn (WorkOrder $record): float => $record->calculateTotal()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Check-in')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(WorkOrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('mechanic_id')
                    ->label('Mekanik')
                    ->relationship('mechanic', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label('Mulai Kerjakan')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (WorkOrder $record): bool => $record->status === WorkOrderStatus::Queued)
                    ->action(function (WorkOrder $record) {
                        try {
                            app(WorkOrderService::class)->startWorkOrder($record->id);
                            Notification::make()
                                ->title('Mulai Dikerjakan!')
                                ->body("PKB {$record->wo_number} dalam status In-Progress.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal!')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('finish')
                    ->label('Selesai (Siap Bayar)')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (WorkOrder $record): bool => $record->status === WorkOrderStatus::InProgress)
                    ->action(function (WorkOrder $record) {
                        try {
                            app(WorkOrderService::class)->finishWorkOrder($record->id);
                            Notification::make()
                                ->title('Motor Selesai Dikerjakan!')
                                ->body("PKB {$record->wo_number} siap ditarik kasir untuk pembayaran.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal!')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('notifyCustomer')
                    ->label('WA Selesai')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->visible(fn (WorkOrder $record): bool => $record->status === WorkOrderStatus::ReadyForPayment && ! empty($record->customer?->phone))
                    ->url(function (WorkOrder $record): string {
                        $phone = preg_replace('/[^0-9]/', '', $record->customer->phone);
                        if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
                        $msg = "Halo Kak {$record->customer->name},\n\nPengerjaan motor {$record->vehicle->motorModel->name} ({$record->vehicle->plate_number}) di cabang kami sudah SELESAI dan siap diambil. Total biaya: Rp " . number_format($record->calculateTotal(), 0, ',', '.') . ".\n\nTerima kasih atas kepercayaan Anda! 🙏🛵";
                        return "https://wa.me/{$phone}?text=" . rawurlencode($msg);
                    }, shouldOpenInNewTab: true),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
