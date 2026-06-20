<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Models\Produk;
use App\Models\StockOpname;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stock opname: menyamakan stok sistem dengan hasil hitung fisik. Tiap produk
 * yang punya selisih menulis satu baris mutasi `penyesuaian` lewat
 * RecordStockMovementAction, sehingga buku besar mutasi_stok tetap jadi sumber
 * kebenaran. Selisih bisa positif (lebih) maupun negatif (kurang).
 */
class StockOpnameAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    /**
     * @param  array<int, array{produk_id: int, stok_fisik: int}>  $items
     */
    public function execute(array $items, Carbon $tanggal, ?string $catatan = null): StockOpname
    {
        if ($items === []) {
            throw new \RuntimeException('Minimal satu produk harus dihitung.');
        }

        return DB::transaction(function () use ($items, $tanggal, $catatan) {
            $opname = StockOpname::create([
                'no_opname' => $this->generateNomor(),
                'tanggal' => $tanggal,
                'total_selisih' => 0,
                'catatan' => $catatan,
            ]);

            $seen = [];
            $totalSelisih = 0;

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $stokFisik = (int) $item['stok_fisik'];

                if ($stokFisik < 0) {
                    throw new \RuntimeException("Stok fisik {$produk->nama} tidak boleh negatif.");
                }

                if (isset($seen[$produk->id])) {
                    throw new \RuntimeException("Produk {$produk->nama} muncul lebih dari sekali.");
                }
                $seen[$produk->id] = true;

                $stokSistem = $produk->stok;
                $selisih = $stokFisik - $stokSistem;

                $opname->details()->create([
                    'produk_id' => $produk->id,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $selisih,
                ]);

                // Hanya tulis mutasi bila ada selisih; stok produk disesuaikan di sini.
                $this->recordMovement->recordAdjustment(
                    produk: $produk,
                    stokFisik: $stokFisik,
                    referensi: "stock_opname:{$opname->id}",
                );

                $totalSelisih += $selisih;
            }

            $opname->update(['total_selisih' => $totalSelisih]);

            return $opname;
        });
    }

    private function generateNomor(): string
    {
        $prefix = 'OPN-'.now()->format('Ymd');
        $last = StockOpname::where('no_opname', 'like', $prefix.'%')
            ->orderByDesc('no_opname')
            ->value('no_opname');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
