<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Tunai = 'tunai';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::Transfer => 'Transfer Bank',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Tunai => 'success',
            self::Transfer => 'info',
        };
    }
}
