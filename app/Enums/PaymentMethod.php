<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';
    case BankTransfer = 'bank_transfer';
    case DebitCard = 'debit_card';
    case CreditCard = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai (Cash)',
            self::Qris => 'QRIS',
            self::BankTransfer => 'Transfer Bank',
            self::DebitCard => 'Kartu Debit',
            self::CreditCard => 'Kartu Kredit',
        };
    }
}
