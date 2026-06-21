<?php

declare(strict_types=1);

namespace App\Support\Laporan;

use Illuminate\Database\Eloquent\Builder;

/**
 * Kontrak satu jenis laporan yang bisa diekspor. Menjadi sumber kebenaran
 * tunggal untuk query dasar & kolom — dipakai bersama oleh halaman Filament
 * (tampilan tabel) dan job export di queue, sehingga tidak ada duplikasi query.
 */
interface DefinisiLaporan
{
    /** Judul laporan, tampil di header PDF & nama sheet. */
    public function judul(): string;

    /** Nama dasar berkas hasil export (tanpa ekstensi & timestamp). */
    public function namaBerkas(): string;

    /**
     * Query dasar laporan tanpa filter UI. Filter & sort interaktif ditambahkan
     * oleh tabel Filament; export memakai daftar id hasil filter (lihat job).
     */
    public function baseQuery(): Builder;

    /** @return list<KolomLaporan> */
    public function kolom(): array;
}
