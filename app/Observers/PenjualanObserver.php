<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Penjualan;
use App\Support\RingkasanPenjualanCache;

/**
 * Invalidasi cache laporan saat data penjualan berubah (bagian 6: cache di-invalidasi
 * lewat observer saat transaksi baru masuk). "saved" mencakup create & update;
 * "deleted" menutup kasus pembatalan/hapus.
 */
class PenjualanObserver
{
    public function saved(Penjualan $penjualan): void
    {
        RingkasanPenjualanCache::lupakan();
    }

    public function deleted(Penjualan $penjualan): void
    {
        RingkasanPenjualanCache::lupakan();
    }
}
