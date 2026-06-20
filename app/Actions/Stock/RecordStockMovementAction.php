<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementType;
use App\Models\MutasiStok;
use App\Models\Produk;

class RecordStockMovementAction
{
    public function execute(
        Produk $produk,
        StockMovementType $tipe,
        int $qtyBase,
        string $referensi,
    ): MutasiStok {
        $delta = $tipe->isInbound() ? $qtyBase : -$qtyBase;
        $stokSebelum = $produk->stok;

        // Operasi atomik — aman dari race condition
        $produk->increment('stok', $delta);

        return MutasiStok::create([
            'produk_id' => $produk->id,
            'tipe' => $tipe,
            'qty' => $qtyBase,
            'referensi' => $referensi,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSebelum + $delta,
        ]);
    }

    /**
     * Penyesuaian stok ke hasil hitung fisik (stock opname). Delta bisa positif
     * (stok lebih) atau negatif (stok kurang). Hanya menulis ledger bila ada
     * selisih; tipe selalu `penyesuaian`.
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

        return MutasiStok::create([
            'produk_id' => $produk->id,
            'tipe' => StockMovementType::Penyesuaian,
            'qty' => abs($delta),
            'referensi' => $referensi,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokFisik,
        ]);
    }
}
