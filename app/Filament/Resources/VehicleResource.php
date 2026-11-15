<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers\WorkOrdersRelationManager;
use App\Models\Customer;
use App\Models\MotorModel;
use App\Models\Vehicle;
use App\Services\WorkOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Database Kendaraan';

    protected static ?string $modelLabel = 'Kendaraan';

    protected static ?string $pluralModelLabel = 'Database Kendaraan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Kendaraan')
                    ->schema([
                        Forms\Components\TextInput::make('plate_number')
                            ->label('Nomor Polisi (Plat Motor)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(25)
                            ->placeholder('Contoh: B 1234 ABC')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase; font-weight: bold; font-family: monospace;'])
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(preg_replace('/\s+/', ' ', trim($state))) : null),
                        Forms\Components\Select::make('motor_model_id')
                            ->label('Model & Tipe Motor')
                            ->relationship('motorModel', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->label('Pemilik / Pelanggan')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->label('Nama Pemilik'),
                                Forms\Components\TextInput::make('phone')->tel()->required()->label('No. WhatsApp'),
                            ]),
                        Forms\Components\TextInput::make('last_km')
                            ->label('Kilometer (KM) Terakhir')
                            ->numeric()
                            ->default(0)
                            ->suffix('KM'),
                        Forms\Components\DatePicker::make('next_service_date')
                            ->label('Estimasi Jadwal Servis Berikutnya'),
                        Forms\Components\TextInput::make('chassis_number')
                            ->label('Nomor Rangka')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('engine_number')
                            ->label('Nomor Mesin')
                            ->maxLength(50),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Plat Nomor')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('motorModel.name')
                    ->label('Tipe Motor')
                    ->description(fn (Vehicle $record): string => $record->motorModel?->brand ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Nama Pemilik')
                    ->description(fn (Vehicle $record): string => $record->customer?->phone ?? '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_km')
                    ->label('KM Terakhir')
                    ->numeric()
                    ->suffix(' KM')
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_service_date')
                    ->label('Jadwal Servis Berikutnya')
                    ->date('d M Y')
                    ->badge()
                    ->color(function (Vehicle $record): string {
                        if (! $record->next_service_date) return 'gray';
                        return $record->next_service_date->isPast() ? 'danger' : 'success';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_orders_count')
                    ->label('Histori Servis')
                    ->counts('workOrders')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('motor_model_id')
                    ->label('Model Motor')
                    ->relationship('motorModel', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendWhatsAppReminder')
                    ->label('WA Reminder')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Vehicle $record): ?string => app(WorkOrderService::class)->generateWhatsAppReminderUrl($record), shouldOpenInNewTab: true)
                    ->visible(fn (Vehicle $record): bool => ! empty($record->customer?->phone)),
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
            WorkOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
