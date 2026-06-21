<?php

declare(strict_types=1);

namespace App\Enums;

use DateTimeInterface;

/**
 * Tipe nilai sebuah kolom laporan. Menentukan cara nilai mentah diformat
 * untuk masing-masing media: Excel butuh angka mentah (agar bisa dijumlah),
 * PDF butuh teks yang sudah diformat rapi (Rupiah, tanggal lokal).
 */
enum TipeKolomLaporan
{
    case Teks;
    case Angka;
    case Uang;
    case Tanggal;

    /** Nilai untuk sel Excel: angka tetap numerik agar bisa diolah di spreadsheet. */
    public function untukExcel(mixed $nilai): mixed
    {
        if ($nilai === null) {
            return '';
        }

        return match ($this) {
            self::Uang, self::Angka => (float) $nilai,
            self::Tanggal => $this->tanggalKeTeks($nilai),
            self::Teks => (string) $nilai,
        };
    }

    /** Nilai untuk sel PDF: selalu teks yang sudah diformat untuk pembaca manusia. */
    public function untukPdf(mixed $nilai): string
    {
        if ($nilai === null) {
            return '-';
        }

        return match ($this) {
            self::Uang => 'Rp '.number_format((float) $nilai, 0, ',', '.'),
            self::Angka => number_format((float) $nilai, 0, ',', '.'),
            self::Tanggal => $this->tanggalKeTeks($nilai),
            self::Teks => (string) $nilai,
        };
    }

    private function tanggalKeTeks(mixed $nilai): string
    {
        if ($nilai instanceof DateTimeInterface) {
            return $nilai->format('d/m/Y H:i');
        }

        return (string) $nilai;
    }
}
