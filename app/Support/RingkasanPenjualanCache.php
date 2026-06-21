<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Penjualan;
use Illuminate\Support\Facades\Cache;

/**
 * Cache angka ringkasan penjualan untuk dashboard (bagian 6: agregasi berat di-cache).
 *
 * Empat agregat (omzet & jumlah transaksi hari/bulan ini) tadinya dihitung ulang
 * pada tiap render dashboard. Hasilnya di-cache di Redis dan di-invalidasi otomatis
 * lewat PenjualanObserver saat ada transaksi baru/berubah/terhapus.
 */
class RingkasanPenjualanCache
{
    /** Tag dipakai agar seluruh cache laporan bisa di-flush sekaligus saat invalidasi. */
    public const TAG = 'laporan';

    private const KEY = 'ringkasan_penjualan';

    /**
     * @return array{omzet_hari: float, omzet_bulan: float, transaksi_hari: int, transaksi_bulan: int}
     */
    public function ambil(): array
    {
        // Key di-scope per tanggal supaya batas hari/bulan tetap akurat walau belum di-invalidasi.
        $key = self::KEY.':'.now()->toDateString();

        return Cache::tags(self::TAG)->remember($key, now()->endOfDay(), function (): array {
            $bulanIni = Penjualan::query()
                ->whereYear('tanggal', now()->year)
                ->whereMonth('tanggal', now()->month);

            return [
                'omzet_hari' => (float) Penjualan::whereDate('tanggal', today())->sum('total'),
                'omzet_bulan' => (float) (clone $bulanIni)->sum('total'),
                'transaksi_hari' => Penjualan::whereDate('tanggal', today())->count(),
                'transaksi_bulan' => (clone $bulanIni)->count(),
            ];
        });
    }

    /**
     * Buang cache laporan. Dipanggil oleh observer saat data transaksi berubah.
     */
    public static function lupakan(): void
    {
        Cache::tags(self::TAG)->flush();
    }
}
