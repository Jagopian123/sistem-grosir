<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Actions\Stock\RecordStockMovementAction;
use App\Enums\StockMovementType;
use App\Models\DetailReturPenjualan;
use App\Models\Penjualan;
use App\Models\ReturPenjualan;
use App\Models\SatuanProduk;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Retur penjualan: barang dikembalikan oleh pelanggan → stok bertambah kembali
 * (mutasi `retur_masuk`). Qty per item tidak boleh melebihi qty terjual dikurangi
 * retur sebelumnya, agar konsisten dengan buku besar mutasi_stok.
 */
class CreateSalesReturnAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    /**
     * @param  array<int, array{satuan_id: int, qty: int}>  $items
     */
    public function execute(Penjualan $penjualan, array $items, Carbon $tanggal, ?string $catatan = null): ReturPenjualan
    {
        return DB::transaction(function () use ($penjualan, $items, $tanggal, $catatan) {
            $retur = ReturPenjualan::create([
                'no_retur' => $this->generateNomor(),
                'penjualan_id' => $penjualan->id,
                'tanggal' => $tanggal,
                'total' => 0,
                'catatan' => $catatan,
            ]);

            foreach ($items as $item) {
                $satuan = SatuanProduk::with('produk')->findOrFail($item['satuan_id']);
                $qty = (int) $item['qty'];

                if ($qty < 1) {
                    throw new \RuntimeException('Qty retur harus minimal 1.');
                }

                $original = $penjualan->details()->where('satuan_id', $satuan->id)->first();

                if ($original === null) {
                    throw new \RuntimeException(
                        "Satuan {$satuan->nama_satuan} tidak ada pada penjualan {$penjualan->no_invoice}."
                    );
                }

                $this->assertRetailable($penjualan, $satuan->id, $qty);

                $hargaSatuan = (float) $original->harga_satuan; // snapshot harga jual asli
                $qtyBase = $qty * $satuan->konversi;

                $retur->details()->create([
                    'produk_id' => $satuan->produk_id,
                    'satuan_id' => $satuan->id,
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $qty * $hargaSatuan,
                ]);

                $this->recordMovement->execute(
                    produk: $satuan->produk,
                    tipe: StockMovementType::ReturMasuk,
                    qtyBase: $qtyBase,
                    referensi: "retur_penjualan:{$retur->id}",
                );
            }

            $retur->update(['total' => $retur->details()->sum('subtotal')]);

            return $retur;
        });
    }

    /**
     * Pastikan qty yang diretur tidak melebihi sisa qty yang masih bisa diretur
     * (qty terjual − qty yang sudah diretur sebelumnya untuk satuan tsb).
     */
    private function assertRetailable(Penjualan $penjualan, int $satuanId, int $qty): void
    {
        $terjual = (int) $penjualan->details()->where('satuan_id', $satuanId)->sum('qty');

        $sudahDiretur = (int) DetailReturPenjualan::query()
            ->where('satuan_id', $satuanId)
            ->whereHas('returPenjualan', fn ($q) => $q->where('penjualan_id', $penjualan->id))
            ->sum('qty');

        $sisa = $terjual - $sudahDiretur;

        if ($qty > $sisa) {
            throw new \RuntimeException(
                "Qty retur ({$qty}) melebihi sisa yang bisa diretur ({$sisa}) untuk salah satu item."
            );
        }
    }

    private function generateNomor(): string
    {
        $prefix = 'RTJ-'.now()->format('Ymd');
        $last = ReturPenjualan::where('no_retur', 'like', $prefix.'%')
            ->orderByDesc('no_retur')
            ->value('no_retur');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
