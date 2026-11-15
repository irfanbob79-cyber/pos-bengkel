<?php

namespace App\Filament\Resources;

use App\Enums\StockOpnameScope;
use App\Enums\StockOpnameStatus;
use App\Filament\Resources\StockOpnameResource\Pages;
use App\Filament\Resources\StockOpnameResource\RelationManagers\ItemsRelationManager;
use App\Models\Branch;
use App\Models\Category;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Manajemen Inventori & Gudang';

    protected static ?string $navigationLabel = 'Sesi Stock Opname';

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Sesi Stock Opname';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Parameter Sesi Opname')
                    ->schema([
                        Forms\Components\TextInput::make('opname_number')
                            ->label('Nomor Sesi Opname')
                            ->default(fn () => 'OPN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                            ->readOnly(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Cabang / Gudang yang Diopname')
                            ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => Auth::user()?->branch_id ?? 1)
                            ->disabled(fn (?StockOpname $record) => $record !== null),
                        Forms\Components\Select::make('scope_type')
                            ->label('Cakupan Opname')
                            ->options(collect(StockOpnameScope::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required()
                            ->default(StockOpnameScope::All->value)
                            ->live()
                            ->disabled(fn (?StockOpname $record) => $record !== null),
                        Forms\Components\Select::make('scope_value')
                            ->label('Pilih Kategori Produk')
                            ->options(Category::pluck('name', 'id'))
                            ->visible(fn (Get $get) => $get('scope_type') === StockOpnameScope::Category->value)
                            ->required(fn (Get $get) => $get('scope_type') === StockOpnameScope::Category->value)
                            ->disabled(fn (?StockOpname $record) => $record !== null),
                        Forms\Components\TextInput::make('scope_value')
                            ->label('Kode Lokasi Rak (Bin Location)')
                            ->placeholder('Contoh: RAK-A02')
                            ->visible(fn (Get $get) => $get('scope_type') === StockOpnameScope::BinLocation->value)
                            ->required(fn (Get $get) => $get('scope_type') === StockOpnameScope::BinLocation->value)
                            ->disabled(fn (?StockOpname $record) => $record !== null),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Sesi')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opname_number')
                    ->label('No. Opname')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope_type')
                    ->label('Cakupan')
                    ->formatStateUsing(fn (StockOpnameScope $state): string => $state->label())
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StockOpnameStatus $state): string => match ($state) {
                        StockOpnameStatus::InProgress => 'warning',
                        StockOpnameStatus::Completed => 'success',
                        StockOpnameStatus::Cancelled => 'danger',
                    })
                    ->formatStateUsing(fn (StockOpnameStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress Scan')
                    ->state(function (StockOpname $record): string {
                        $total = $record->items()->count();
                        $scanned = $record->items()->where('is_scanned', true)->count();
                        $pct = $total > 0 ? round(($scanned / $total) * 100) : 0;

                        return "{$scanned}/{$total} item ({$pct}%)";
                    })
                    ->badge()
                    ->color(fn (StockOpname $record): string => $record->items()->where('is_scanned', false)->count() > 0 ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(StockOpnameStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Lembar Hitung')
                    ->icon('heroicon-o-pencil-square'),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui Penyesuaian')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (StockOpname $record): bool => $record->status === StockOpnameStatus::InProgress)
                    ->requiresConfirmation()
                    ->modalHeading('Approval Penyesuaian Saldo Stok')
                    ->modalDescription('Stok sistem akan disesuaikan otomatis dengan hasil hitung fisik dan selisihnya akan dicatat ke Kartu Stok.')
                    ->action(function (StockOpname $record) {
                        try {
                            app(StockOpnameService::class)->approveSession($record->id, Auth::id() ?? 1);
                            Notification::make()
                                ->title('Stock Opname Berhasil Disetujui!')
                                ->body("Saldo stok cabang {$record->branch->name} telah diperbarui.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyetujui opname!')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }
}
