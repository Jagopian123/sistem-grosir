<?php

declare(strict_types=1);

namespace App\Support\Laporan;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

/**
 * Merender laporan ke PDF (A4 landscape) memakai dompdf. Nilai sudah diformat
 * sebagai teks (Rupiah, tanggal) agar siap dibaca. Kembalikan byte mentah
 * supaya pemanggil (job) menyimpannya ke storage.
 */
class PenulisPdfLaporan
{
    public function render(DefinisiLaporan $definisi, Builder $query): string
    {
        $kolom = $definisi->kolom();

        $baris = $query->get()->map(
            fn (object $record): array => array_map(
                fn (KolomLaporan $k): string => $k->nilaiPdf($record),
                $kolom,
            ),
        );

        return Pdf::loadView('pdf.laporan', [
            'judul' => $definisi->judul(),
            'header' => array_map(fn (KolomLaporan $k): string => $k->label, $kolom),
            'baris' => $baris,
            'dicetak' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->output();
    }
}
