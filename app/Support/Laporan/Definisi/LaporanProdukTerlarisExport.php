<?php

declare(strict_types=1);

namespace App\Support\Laporan\Definisi;

use App\Enums\TipeKolomLaporan;
use App\Models\Produk;
use App\Support\Laporan\DefinisiLaporan;
use App\Support\Laporan\KolomLaporan;
use Illuminate\Database\Eloquent\Builder;

final class LaporanProdukTerlarisExport implements DefinisiLaporan
{
    public function judul(): string
    {
        return 'Laporan Produk Terlaris';
    }

    public function namaBerkas(): string
    {
        return 'laporan-produk-terlaris';
    }

    public function baseQuery(): Builder
    {
        return Produk::query()
            ->join('detail_penjualans', 'detail_penjualans.produk_id', '=', 'produks.id')
            ->join('penjualans', 'penjualans.id', '=', 'detail_penjualans.penjualan_id')
            ->selectRaw('
                produks.id,
                produks.nama as produk_nama,
                SUM(detail_penjualans.qty) as total_qty,
                SUM(detail_penjualans.subtotal) as total_omzet
            ')
            ->groupBy('produks.id', 'produks.nama')
            ->orderByDesc('total_omzet');
    }

    public function kolom(): array
    {
        return [
            new KolomLaporan('Produk', fn (Produk $r) => $r->getAttribute('produk_nama')),
            new KolomLaporan('Total Qty Terjual', fn (Produk $r): float => (float) $r->getAttribute('total_qty'), TipeKolomLaporan::Angka),
            new KolomLaporan('Total Omzet', fn (Produk $r): float => (float) $r->getAttribute('total_omzet'), TipeKolomLaporan::Uang),
        ];
    }
}
