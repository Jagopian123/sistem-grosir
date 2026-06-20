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
}
