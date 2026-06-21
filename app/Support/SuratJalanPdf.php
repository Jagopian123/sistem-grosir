<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Penjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Database\Eloquent\Collection;

/**
 * Perakit dokumen PDF Surat Jalan. Dipakai bersama oleh cetak satuan
 * (sinkron, langsung di-stream) dan cetak massal (di-render di queue).
 * Relasi yang dibutuhkan blade selalu di-eager-load di satu tempat ini.
 */
class SuratJalanPdf
{
    /** @var list<string> */
    private const RELASI = ['pelanggan', 'sopir', 'details.produk', 'details.satuan'];

    /** Satu surat jalan, A4 portrait. */
    public function satu(Penjualan $penjualan): PdfDocument
    {
        $penjualan->loadMissing(self::RELASI);

        return Pdf::loadView('pdf.surat-jalan', ['penjualan' => $penjualan])
            ->setPaper('a4', 'portrait');
    }

    /**
     * Banyak surat jalan jadi satu PDF (satu per halaman). Kembalikan byte mentah
     * agar pemanggil (job) bisa menyimpannya ke storage.
     */
    public function banyak(Collection $penjualans): string
    {
        $penjualans->loadMissing(self::RELASI);

        return Pdf::loadView('pdf.surat-jalan-massal', ['penjualans' => $penjualans])
            ->setPaper('a4', 'portrait')
            ->output();
    }
}
