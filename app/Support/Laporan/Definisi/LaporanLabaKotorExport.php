<?php

declare(strict_types=1);

namespace App\Support\Laporan\Definisi;

use App\Enums\TipeKolomLaporan;
use App\Models\Penjualan;
use App\Support\Laporan\DefinisiLaporan;
use App\Support\Laporan\KolomLaporan;
use Illuminate\Database\Eloquent\Builder;

final class LaporanLabaKotorExport implements DefinisiLaporan
{
    public function judul(): string
    {
        return 'Laporan Laba Kotor';
    }

    public function namaBerkas(): string
    {
        return 'laporan-laba-kotor';
    }

    public function baseQuery(): Builder
    {
        return Penjualan::query()
            ->join('detail_penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
            ->join('satuan_produks', 'satuan_produks.id', '=', 'detail_penjualans.satuan_id')
            ->join('produks', 'produks.id', '=', 'detail_penjualans.produk_id')
            ->join('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id')
            ->selectRaw('
                penjualans.id,
                penjualans.no_invoice,
                penjualans.tanggal,
                pelanggans.nama_toko as pelanggan_nama,
                penjualans.total as omzet,
                COALESCE(SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli), 0) as hpp,
                penjualans.total - COALESCE(SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli), 0) as laba_kotor
            ')
            ->groupBy(
                'penjualans.id',
                'penjualans.no_invoice',
                'penjualans.tanggal',
                'pelanggans.nama_toko',
                'penjualans.total'
            )
            ->orderByDesc('penjualans.tanggal');
    }

    public function kolom(): array
    {
        return [
            new KolomLaporan('No. Invoice', fn (Penjualan $r): string => $r->no_invoice),
            new KolomLaporan('Tanggal', fn (Penjualan $r) => $r->tanggal, TipeKolomLaporan::Tanggal),
            new KolomLaporan('Pelanggan', fn (Penjualan $r) => $r->getAttribute('pelanggan_nama')),
            new KolomLaporan('Omzet', fn (Penjualan $r): float => (float) $r->getAttribute('omzet'), TipeKolomLaporan::Uang),
            new KolomLaporan('HPP', fn (Penjualan $r): float => (float) $r->getAttribute('hpp'), TipeKolomLaporan::Uang),
            new KolomLaporan('Laba Kotor', fn (Penjualan $r): float => (float) $r->getAttribute('laba_kotor'), TipeKolomLaporan::Uang),
        ];
    }
}
