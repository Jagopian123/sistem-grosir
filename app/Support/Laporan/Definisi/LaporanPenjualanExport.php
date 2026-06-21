<?php

declare(strict_types=1);

namespace App\Support\Laporan\Definisi;

use App\Enums\TipeKolomLaporan;
use App\Models\Penjualan;
use App\Support\Laporan\DefinisiLaporan;
use App\Support\Laporan\KolomLaporan;
use Illuminate\Database\Eloquent\Builder;

final class LaporanPenjualanExport implements DefinisiLaporan
{
    public function judul(): string
    {
        return 'Laporan Penjualan';
    }

    public function namaBerkas(): string
    {
        return 'laporan-penjualan';
    }

    public function baseQuery(): Builder
    {
        return Penjualan::query()
            ->with(['pelanggan'])
            ->latest('tanggal');
    }

    public function kolom(): array
    {
        return [
            new KolomLaporan('No. Invoice', fn (Penjualan $r): string => $r->no_invoice),
            new KolomLaporan('Tanggal', fn (Penjualan $r) => $r->tanggal, TipeKolomLaporan::Tanggal),
            new KolomLaporan('Pelanggan', fn (Penjualan $r): ?string => $r->pelanggan?->nama_toko),
            new KolomLaporan('Total', fn (Penjualan $r): float => (float) $r->total, TipeKolomLaporan::Uang),
            new KolomLaporan('Metode Bayar', fn (Penjualan $r): string => $r->metode_bayar->label()),
        ];
    }
}
