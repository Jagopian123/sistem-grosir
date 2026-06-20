<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceFormat: string
{
    case A4 = 'a4';
    case Thermal58 = 'thermal58';
    case Thermal80 = 'thermal80';

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'Invoice A4 (PDF)',
            self::Thermal58 => 'Struk Thermal 58mm',
            self::Thermal80 => 'Struk Thermal 80mm',
        };
    }

    public function isThermal(): bool
    {
        return $this !== self::A4;
    }

    /** Lebar kertas struk thermal dalam milimeter. */
    public function widthMm(): int
    {
        return match ($this) {
            self::A4 => 0,
            self::Thermal58 => 58,
            self::Thermal80 => 80,
        };
    }
}
