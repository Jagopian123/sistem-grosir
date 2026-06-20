<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpiryStatus: string
{
    case Aman = 'aman';
    case Mendekati = 'mendekati';
    case Kadaluarsa = 'kadaluarsa';

    public function label(): string
    {
        return match ($this) {
            self::Aman => 'Aman',
            self::Mendekati => 'Mendekati ED',
            self::Kadaluarsa => 'Kadaluarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aman => 'success',
            self::Mendekati => 'warning',
            self::Kadaluarsa => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Aman => 'heroicon-o-check-circle',
            self::Mendekati => 'heroicon-o-clock',
            self::Kadaluarsa => 'heroicon-o-x-circle',
        };
    }
}
