<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Produk;
use Carbon\CarbonInterface;

class RecordStockMovementAction
{
    public function __construct(
        private readonly AllocateBatchAction $allocateBatch,
    ) {}

    /**
     * Catat satu pergerakan stok ke buku besar mutasi_stok. Untuk produk yang
     * dilacak kadaluarsanya, pergerakan ini juga disalurkan ke sub-buku besar
     * batch (masuk → buat batch, keluar → konsumsi FEFO).
     */
    public function execute(
        Produk $produk,
        StockMovementType $tipe,
        int $qtyBase,
        string $referensi,
        ?CarbonInterface $tanggalKadaluarsa = null,
        ?string $kodeBatch = null,
    ): MutasiStok {
        $delta = $tipe->isInbound() ? $qtyBase : -$qtyBase;
        $stokSebelum = $produk->stok;

        // Operasi atomik — aman dari race condition
        $produk->increment('stok', $delta);

        $mutasi = MutasiStok::create([
            'produk_id' => $produk->id,
            'tipe' => $tipe,
            'qty' => $qtyBase,
            'referensi' => $referensi,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSebelum + $delta,
        ]);

        if ($produk->lacak_kadaluarsa) {
            if ($tipe->isInbound()) {
                $this->allocateBatch->recordInbound($produk, $qtyBase, $referensi, $tanggalKadaluarsa, $kodeBatch);
            } else {
                $this->allocateBatch->consumeFefo($produk, $qtyBase, $referensi);
            }
        }

        return $mutasi;
    }

    /**
     * Penyesuaian stok ke hasil hitung fisik (stock opname). Delta bisa positif
     * (stok lebih) atau negatif (stok kurang). Hanya menulis ledger bila ada
     * selisih; tipe selalu `penyesuaian`. Produk berbatch ikut disesuaikan:
     * selisih positif membuat batch baru tanpa ED, selisih negatif konsumsi FEFO.
     */
    public function recordAdjustment(Produk $produk, int $stokFisik, string $referensi): ?MutasiStok
    {
        $stokSebelum = $produk->stok;
        $delta = $stokFisik - $stokSebelum;

        if ($delta === 0) {
            return null;
        }

        // Operasi atomik — increment menerima delta negatif untuk pengurangan.
        $produk->increment('stok', $delta);

        $mutasi = MutasiStok::create([
            'produk_id' => $produk->id,
            'tipe' => StockMovementType::Penyesuaian,
            'qty' => abs($delta),
            'referensi' => $referensi,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokFisik,
        ]);

        if ($produk->lacak_kadaluarsa) {
            if ($delta > 0) {
                $this->allocateBatch->recordInbound($produk, $delta, $referensi);
            } else {
                $this->allocateBatch->consumeFefo($produk, abs($delta), $referensi);
            }
        }

        return $mutasi;
    }
}
