<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryStatus: string
{
    case SiapKirim = 'siap_kirim';
    case Dikirim = 'dikirim';
    case Terkirim = 'terkirim';

    public function label(): string
    {
        return match ($this) {
            self::SiapKirim => 'Siap Kirim',
            self::Dikirim => 'Dikirim',
            self::Terkirim => 'Terkirim',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SiapKirim => 'warning',
            self::Dikirim => 'info',
            self::Terkirim => 'success',
        };
    }
}
