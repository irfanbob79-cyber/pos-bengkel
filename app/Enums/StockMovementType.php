<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case SaleOut = 'sale_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case OpnameAdjustment = 'opname_adjustment';
    case InitialImport = 'initial_import';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseIn => 'Penerimaan Pembelian (PO)',
            self::SaleOut => 'Penjualan Kasir (POS)',
            self::TransferIn => 'Transfer Masuk',
            self::TransferOut => 'Transfer Keluar',
            self::OpnameAdjustment => 'Penyesuaian Opname',
            self::InitialImport => 'Saldo Awal / Import',
        };
    }
}
