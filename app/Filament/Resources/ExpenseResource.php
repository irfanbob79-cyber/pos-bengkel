<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Branch;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan & Pembelian';

    protected static ?string $navigationLabel = 'Beban Operasional Cabang';

    protected static ?string $modelLabel = 'Beban Operasional';

    protected static ?string $pluralModelLabel = 'Beban Operasional Cabang';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pencatatan Biaya & Beban')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Cabang Beban')
                            ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => Auth::user()?->branch_id ?? 1)
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Kategori Beban')
                            ->options([
                                'Listrik & Air' => 'Listrik & Air (Utilitas)',
                                'Gaji Pokok Karyawan' => 'Gaji Pokok Karyawan',
                                'Konsumsi & Logistik' => 'Konsumsi & Logistik Harian',
                                'Sewa Gedung / Ruko' => 'Sewa Gedung / Ruko',
                                'Perawatan Alat Bengkel' => 'Perawatan & Servis Alat Bengkel',
                                'ATK & Cetak Struk' => 'ATK & Kertas Struk / Label',
                                'Lain-lain' => 'Beban Lain-lain',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\DatePicker::make('expense_date')
                            ->label('Tanggal Pengeluaran')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Biaya')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->placeholder('Contoh: 150000'),
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => Auth::id()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Keterangan / Nomor Bukti / Kwitansi')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori Beban')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal Biaya')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn (Expense $record): ?string => $record->notes),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori Beban')
                    ->options([
                        'Listrik & Air' => 'Listrik & Air',
                        'Gaji Pokok Karyawan' => 'Gaji Pokok Karyawan',
                        'Konsumsi & Logistik' => 'Konsumsi & Logistik',
                        'Sewa Gedung / Ruko' => 'Sewa Gedung / Ruko',
                        'Perawatan Alat Bengkel' => 'Perawatan Alat Bengkel',
                        'ATK & Cetak Struk' => 'ATK & Cetak Struk',
                        'Lain-lain' => 'Lain-lain',
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
