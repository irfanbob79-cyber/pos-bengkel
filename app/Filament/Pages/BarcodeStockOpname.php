<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Services\StockOpnameService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class BarcodeStockOpname extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $navigationGroup = 'Manajemen Inventori & Gudang';

    protected static ?string $navigationLabel = 'Cek Stok Cepat (Quick Scan)';

    protected static ?string $title = 'Pemindai Barcode & Cek Stok Rak';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.barcode-stock-opname';

    public ?int $branch_id = null;

    public ?string $search_barcode = '';

    public ?array $productResult = null;

    public function mount(): void
    {
        $this->branch_id = Auth::user()?->branch_id ?? Branch::first()?->id ?? 1;
        $this->form->fill([
            'branch_id' => $this->branch_id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('branch_id')
                    ->label('Pilih Cabang')
                    ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->live(),
                TextInput::make('search_barcode')
                    ->label('Scan Barcode / Masukkan SKU')
                    ->placeholder('Arahkan barcode scanner atau ketik SKU/Barcode...')
                    ->autofocus()
                    ->extraInputAttributes([
                        'id' => 'quick-scan-input',
                        'autocomplete' => 'off',
                    ]),
            ])->columns(2);
    }

    public function performScan(): void
    {
        if (empty($this->search_barcode)) {
            return;
        }

        try {
            $service = app(StockOpnameService::class);
            $this->productResult = $service->quickScanCheck(
                branchId: (int) $this->branch_id,
                barcodeOrSku: trim($this->search_barcode)
            );

            $this->search_barcode = '';
            $this->dispatch('scan-completed');
        } catch (\Exception $e) {
            $this->productResult = null;
            Notification::make()
                ->title('Pencarian Gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
