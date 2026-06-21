<?php

declare(strict_types=1);

namespace App\Support\Laporan;

use App\Enums\TipeKolomLaporan;
use Closure;

/**
 * Definisi satu kolom laporan untuk keperluan export. Memetakan sebuah baris
 * data menjadi nilai mentah, lalu memformatnya sesuai media (Excel/PDF).
 */
final class KolomLaporan
{
    /**
     * @param  Closure(object): mixed  $nilai  Pengambil nilai mentah dari satu record
     */
    public function __construct(
        public readonly string $label,
        public readonly Closure $nilai,
        public readonly TipeKolomLaporan $tipe = TipeKolomLaporan::Teks,
    ) {}

    public function nilaiExcel(object $record): mixed
    {
        return $this->tipe->untukExcel(($this->nilai)($record));
    }

    public function nilaiPdf(object $record): string
    {
        return $this->tipe->untukPdf(($this->nilai)($record));
    }
}
