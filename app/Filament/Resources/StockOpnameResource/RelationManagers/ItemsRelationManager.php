<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Services\StockOpnameService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Lembar Kerja Fisik (Scan-to-Count)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('scanned_qty')
                    ->label('Jumlah Hitung Fisik')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var StockOpname $opname */
        $opname = $this->getOwnerRecord();
        $isEditable = $opname->status->value === 'in_progress';

        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Nama Sparepart')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('system_qty')
                    ->label('Stok Sistem')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('scanned_qty')
                    ->label('Hitung Fisik')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('difference_qty')
                    ->label('Selisih (+/-)')
                    ->badge()
                    ->color(fn (float $state): string => $state == 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (float $state): string => ($state > 0 ? "+{$state}" : (string) $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_scanned')
                    ->label('Status Hitung')
                    ->badge()
                    ->state(fn (StockOpnameItem $record): string => $record->is_scanned ? 'Sudah Di-scan' : 'Belum Dihitung')
                    ->color(fn (StockOpnameItem $record): string => $record->is_scanned ? 'success' : 'danger'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('scanBarcode')
                    ->label('Scan / Tembak Barcode (+1)')
                    ->icon('heroicon-o-camera')
                    ->color('primary')
                    ->visible($isEditable)
                    ->form([
                        Forms\Components\TextInput::make('barcode')
                            ->label('Scan Barcode atau Ketik SKU')
                            ->required()
                            ->autofocus()
                            ->placeholder('Arahkan scanner ke barcode...'),
                        Forms\Components\TextInput::make('qty')
                            ->label('Kuantitas (default: 1)')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (array $data) use ($opname) {
                        try {
                            app(StockOpnameService::class)->recordScan(
                                opnameId: $opname->id,
                                barcodeOrSku: trim($data['barcode']),
                                qty: (float) $data['qty'],
                                replace: false
                            );

                            Notification::make()
                                ->title('Item berhasil di-scan!')
                                ->body("Barcode: {$data['barcode']} (+{$data['qty']})")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mencatat scan!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah Fisik')
                    ->visible($isEditable)
                    ->mutateRecordDataUsing(function (StockOpnameItem $record, array $data): array {
                        $data['difference_qty'] = $data['scanned_qty'] - $record->system_qty;
                        $data['is_scanned'] = true;

                        return $data;
                    }),
            ])
            ->bulkActions([]);
    }
}
