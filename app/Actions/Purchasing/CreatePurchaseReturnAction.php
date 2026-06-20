<?php

declare(strict_types=1);

namespace App\Actions\Purchasing;

use App\Actions\Stock\RecordStockMovementAction;
use App\Enums\StockMovementType;
use App\Models\DetailReturPembelian;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\ReturPembelian;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Retur pembelian: barang dikembalikan ke supplier → stok berkurang
 * (mutasi `retur_keluar`). Qty (satuan dasar) tidak boleh melebihi qty dibeli
 * dikurangi retur sebelumnya, dan tidak boleh melebihi stok fisik saat ini.
 */
class CreatePurchaseReturnAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    /**
     * @param  array<int, array{produk_id: int, qty: int}>  $items
     */
    public function execute(Pembelian $pembelian, array $items, Carbon $tanggal, ?string $catatan = null): ReturPembelian
    {
        return DB::transaction(function () use ($pembelian, $items, $tanggal, $catatan) {
            $retur = ReturPembelian::create([
                'no_retur' => $this->generateNomor(),
                'pembelian_id' => $pembelian->id,
                'tanggal' => $tanggal,
                'total' => 0,
                'catatan' => $catatan,
            ]);

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $qty = (int) $item['qty']; // satuan dasar

                if ($qty < 1) {
                    throw new \RuntimeException('Qty retur harus minimal 1.');
                }

                $original = $pembelian->details()->where('produk_id', $produk->id)->first();

                if ($original === null) {
                    throw new \RuntimeException(
                        "Produk {$produk->nama} tidak ada pada pembelian {$pembelian->no_pembelian}."
                    );
                }

                $this->assertRetailable($pembelian, $produk, $qty);

                $hargaBeli = (float) $original->harga_beli; // snapshot harga beli asli

                $retur->details()->create([
                    'produk_id' => $produk->id,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $qty * $hargaBeli,
                ]);

                $this->recordMovement->execute(
                    produk: $produk,
                    tipe: StockMovementType::ReturKeluar,
                    qtyBase: $qty,
                    referensi: "retur_pembelian:{$retur->id}",
                );
            }

            $retur->update(['total' => $retur->details()->sum('subtotal')]);

            return $retur;
        });
    }

    /**
     * Pastikan qty retur tidak melebihi sisa yang bisa diretur (qty dibeli −
     * retur sebelumnya) maupun stok fisik produk saat ini.
     */
    private function assertRetailable(Pembelian $pembelian, Produk $produk, int $qty): void
    {
        $dibeli = (int) $pembelian->details()->where('produk_id', $produk->id)->sum('qty');

        $sudahDiretur = (int) DetailReturPembelian::query()
            ->where('produk_id', $produk->id)
            ->whereHas('returPembelian', fn ($q) => $q->where('pembelian_id', $pembelian->id))
            ->sum('qty');

        $sisa = $dibeli - $sudahDiretur;

        if ($qty > $sisa) {
            throw new \RuntimeException(
                "Qty retur ({$qty}) melebihi sisa yang bisa diretur ({$sisa}) untuk {$produk->nama}."
            );
        }

        if ($qty > $produk->stok) {
            throw new \RuntimeException(
                "Qty retur ({$qty}) melebihi stok {$produk->nama} saat ini ({$produk->stok})."
            );
        }
    }

    private function generateNomor(): string
    {
        $prefix = 'RTB-'.now()->format('Ymd');
        $last = ReturPembelian::where('no_retur', 'like', $prefix.'%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
