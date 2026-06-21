<?php

declare(strict_types=1);

namespace App\Support\Laporan\Definisi;

use App\Enums\TipeKolomLaporan;
use App\Models\Produk;
use App\Support\Laporan\DefinisiLaporan;
use App\Support\Laporan\KolomLaporan;
use Illuminate\Database\Eloquent\Builder;

final class LaporanStokExport implements DefinisiLaporan
{
    public function judul(): string
    {
        return 'Laporan Stok Saat Ini';
    }

    public function namaBerkas(): string
    {
        return 'laporan-stok';
    }

    public function baseQuery(): Builder
    {
        return Produk::query()
            ->with('kategori')
            ->select(['id', 'kategori_id', 'nama', 'satuan_dasar', 'stok', 'stok_min', 'harga_beli', 'aktif'])
            ->orderBy('nama');
    }

    public function kolom(): array
    {
        return [
            new KolomLaporan('Produk', fn (Produk $r): string => $r->nama),
            new KolomLaporan('Kategori', fn (Produk $r): ?string => $r->kategori?->nama),
            new KolomLaporan('Stok', fn (Produk $r): float => (float) $r->stok, TipeKolomLaporan::Angka),
            new KolomLaporan('Stok Min', fn (Produk $r): float => (float) $r->stok_min, TipeKolomLaporan::Angka),
            new KolomLaporan('Satuan', fn (Produk $r): string => $r->satuan_dasar),
            new KolomLaporan('HPP/Satuan Dasar', fn (Produk $r): float => (float) $r->harga_beli, TipeKolomLaporan::Uang),
            new KolomLaporan('Nilai Stok', fn (Produk $r): float => (float) $r->harga_beli * $r->stok, TipeKolomLaporan::Uang),
        ];
    }
}
