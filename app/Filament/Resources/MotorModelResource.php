<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MotorModelResource\Pages;
use App\Models\MotorModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MotorModelResource extends Resource
{
    protected static ?string $model = MotorModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Master Katalog & Harga';

    protected static ?string $navigationLabel = 'Tipe & Model Motor';

    protected static ?string $modelLabel = 'Model Motor';

    protected static ?string $pluralModelLabel = 'Tipe & Model Motor';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Model Motor')
                    ->schema([
                        Forms\Components\Select::make('brand')
                            ->label('Pabrikan / Brand Motor')
                            ->options([
                                'Honda' => 'Honda',
                                'Yamaha' => 'Yamaha',
                                'Suzuki' => 'Suzuki',
                                'Kawasaki' => 'Kawasaki',
                                'Vespa / Piaggio' => 'Vespa / Piaggio',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Model & Seri')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Vario 125 eSP / NMAX 155'),
                        Forms\Components\TextInput::make('engine_capacity')
                            ->label('Kapasitas Mesin (CC)')
                            ->numeric()
                            ->suffix('cc')
                            ->placeholder('110, 125, 150, 155, 250'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand')
                    ->label('Pabrikan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Honda' => 'danger',
                        'Yamaha' => 'info',
                        'Kawasaki' => 'success',
                        'Suzuki' => 'primary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Model Motor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('engine_capacity')
                    ->label('Kapasitas Mesin')
                    ->suffix(' cc')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Part Kompatibel')
                    ->counts('products')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('vehicles_count')
                    ->label('Kendaraan Terdaftar')
                    ->counts('vehicles')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->label('Pabrikan')
                    ->options([
                        'Honda' => 'Honda',
                        'Yamaha' => 'Yamaha',
                        'Suzuki' => 'Suzuki',
                        'Kawasaki' => 'Kawasaki',
                        'Vespa / Piaggio' => 'Vespa / Piaggio',
                    ]),
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
            'index' => Pages\ListMotorModels::route('/'),
            'create' => Pages\CreateMotorModel::route('/create'),
            'edit' => Pages\EditMotorModel::route('/{record}/edit'),
        ];
    }
}
