<?php

namespace App\Filament\Resources;

use App\Enums\CommissionType;
use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Layanan & Bengkel';

    protected static ?string $navigationLabel = 'Katalog Jasa & Komisi';

    protected static ?string $modelLabel = 'Jasa Servis';

    protected static ?string $pluralModelLabel = 'Katalog Jasa & Komisi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Layanan Jasa')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Layanan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30)
                            ->placeholder('Contoh: JAS-001')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Jasa / Layanan')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Contoh: Servis Ringan / Ganti Oli / Tune Up'),
                        Forms\Components\TextInput::make('default_price')
                            ->label('Tarif Standar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('50.000'),
                    ])->columns(3),

                Forms\Components\Section::make('Pengaturan Komisi Mekanik')
                    ->schema([
                        Forms\Components\Select::make('commission_type')
                            ->label('Jenis Komisi')
                            ->options(collect(CommissionType::cases())->mapWithKeys(fn (CommissionType $type) => [$type->value => $type->label()]))
                            ->required()
                            ->default(CommissionType::Percentage->value)
                            ->live(),
                        Forms\Components\TextInput::make('commission_value')
                            ->label('Nilai Komisi')
                            ->required()
                            ->numeric()
                            ->prefix(fn (Get $get): ?string => $get('commission_type') === CommissionType::Fixed->value ? 'Rp' : null)
                            ->suffix(fn (Get $get): ?string => $get('commission_type') === CommissionType::Percentage->value ? '%' : null)
                            ->placeholder('Contoh: 40 (untuk 40%) atau 15000'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Jasa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_price')
                    ->label('Tarif Standar')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_value')
                    ->label('Komisi Mekanik')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(function (Service $record): string {
                        if ($record->commission_type === CommissionType::Percentage) {
                            $calc = ($record->default_price * $record->commission_value) / 100;

                            return "{$record->commission_value}% (~Rp " . number_format($calc, 0, ',', '.') . ')';
                        }

                        return 'Rp ' . number_format($record->commission_value, 0, ',', '.');
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
