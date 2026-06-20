<?php

declare(strict_types=1);

namespace App\Actions\Purchasing;

use App\Actions\Stock\RecordStockMovementAction;
use App\Enums\StockMovementType;
use App\Models\Pembelian;
use App\Models\Produk;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReceiveStockAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    /**
     * @param  array<int, array{produk_id: int, qty: int, harga_beli: float|int}>  $items
     */
    public function execute(Supplier $supplier, array $items, Carbon $tanggal): Pembelian
    {
        return DB::transaction(function () use ($supplier, $items, $tanggal) {
            $pembelian = Pembelian::create([
                'no_pembelian' => $this->generateNomor(),
                'supplier_id' => $supplier->id,
                'tanggal' => $tanggal,
                'total' => 0,
            ]);

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $qty = (int) $item['qty'];
                $hargaBeli = (float) $item['harga_beli'];

                $pembelian->details()->create([
                    'produk_id' => $produk->id,
                    'qty' => $qty,
                    'harga_beli' => $hargaBeli,
                    'subtotal' => $qty * $hargaBeli,
                ]);

                // Update HPP ke harga beli terbaru
                $produk->update(['harga_beli' => $hargaBeli]);

                $this->recordMovement->execute(
                    produk: $produk->fresh(),
                    tipe: StockMovementType::Masuk,
                    qtyBase: $qty,
                    referensi: "pembelian:{$pembelian->id}",
                );
            }

            $pembelian->update(['total' => $pembelian->details()->sum('subtotal')]);

            return $pembelian;
        });
    }

    private function generateNomor(): string
    {
        $prefix = 'PB-'.now()->format('Ymd');
        $last = Pembelian::where('no_pembelian', 'like', $prefix.'%')
            ->orderByDesc('no_pembelian')
            ->value('no_pembelian');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
