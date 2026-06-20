<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Models\BatchStok;
use App\Models\Produk;
use Carbon\CarbonInterface;

/**
 * Mengelola sub-buku besar batch untuk produk yang dilacak kadaluarsanya.
 * Selalu dipanggil dari RecordStockMovementAction setelah buku besar mutasi_stok
 * ditulis, sehingga batch tetap selaras dengan pergerakan stok.
 *
 * - Stok masuk → buat satu batch (dengan/atau tanpa ED).
 * - Stok keluar → kurangi qty_sisa secara FEFO (First Expired First Out):
 *   batch ber-ED terdekat dikonsumsi lebih dulu, batch tanpa ED paling akhir.
 */
class AllocateBatchAction
{
    public function recordInbound(
        Produk $produk,
        int $qty,
        string $sumber,
        ?CarbonInterface $tanggalKadaluarsa = null,
        ?string $kodeBatch = null,
    ): BatchStok {
        return BatchStok::create([
            'produk_id' => $produk->id,
            'kode_batch' => $kodeBatch,
            'tanggal_kadaluarsa' => $tanggalKadaluarsa,
            'qty_masuk' => $qty,
            'qty_sisa' => $qty,
            'sumber' => $sumber,
            'tanggal_masuk' => now(),
        ]);
    }

    /**
     * Konsumsi stok keluar dari batch secara FEFO. Sisa yang tak terpenuhi
     * (mis. stok lama yang masuk sebelum pelacakan batch diaktifkan) diabaikan;
     * buku besar mutasi_stok tetap menjadi sumber kebenaran untuk produk.stok.
     *
     * @return int qty yang tidak bisa dialokasikan ke batch mana pun
     */
    public function consumeFefo(Produk $produk, int $qty, string $sumber): int
    {
        $sisaDikonsumsi = $qty;

        $batches = BatchStok::query()
            ->where('produk_id', $produk->id)
            ->where('qty_sisa', '>', 0)
            ->orderByRaw('tanggal_kadaluarsa IS NULL') // batch ber-ED (0) dulu, tanpa ED (1) terakhir
            ->orderBy('tanggal_kadaluarsa')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($sisaDikonsumsi <= 0) {
                break;
            }

            $ambil = min($sisaDikonsumsi, $batch->qty_sisa);
            $batch->decrement('qty_sisa', $ambil);
            $sisaDikonsumsi -= $ambil;
        }

        return $sisaDikonsumsi;
    }
}
