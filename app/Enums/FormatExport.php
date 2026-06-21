<?php

declare(strict_types=1);

namespace App\Enums;

/** Format berkas hasil export laporan. */
enum FormatExport: string
{
    case Excel = 'excel';
    case Pdf = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::Excel => 'Excel',
            self::Pdf => 'PDF',
        };
    }

    /** Ekstensi berkas untuk format ini. */
    public function ekstensi(): string
    {
        return match ($this) {
            self::Excel => 'xlsx',
            self::Pdf => 'pdf',
        };
    }
}
