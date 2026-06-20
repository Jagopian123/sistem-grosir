<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case ReturMasuk = 'retur_masuk';
    case ReturKeluar = 'retur_keluar';
    case Penyesuaian = 'penyesuaian';

    public function isInbound(): bool
    {
        return in_array($this, [self::Masuk, self::ReturMasuk]);
    }

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Stok Masuk',
            self::Keluar => 'Penjualan',
            self::ReturMasuk => 'Retur Masuk',
            self::ReturKeluar => 'Retur Keluar',
            self::Penyesuaian => 'Penyesuaian',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Masuk, self::ReturMasuk => 'success',
            self::Keluar, self::ReturKeluar => 'danger',
            self::Penyesuaian => 'warning',
        };
    }
}
