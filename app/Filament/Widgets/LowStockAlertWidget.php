<?php

namespace App\Filament\Widgets;

use App\Models\BranchStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertWidget extends BaseWidget
{
    protected static ?string $heading = 'Peringatan Stok Menipis (Restock Alert)';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BranchStock::query()
                    ->with(['product.brand', 'branch'])
                    ->whereColumn('qty', '<=', 'min_stock')
                    ->orderBy('qty', 'asc')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produk')
                    ->description(fn (BranchStock $record): string => $record->branch?->name ?? '-')
                    ->weight('semibold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Sisa')
                    ->badge()
                    ->color('danger')
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min')
                    ->color('gray')
                    ->alignment('center'),
            ])
            ->actions([
                Tables\Actions\Action::make('order')
                    ->label('PO')
                    ->icon('heroicon-m-shopping-cart')
                    ->url(url('/admin/purchases/create'))
                    ->button()
                    ->size('xs')
                    ->color('warning'),
            ])
            ->paginated(false);
    }
}
