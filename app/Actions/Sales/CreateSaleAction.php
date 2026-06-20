<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Actions\Stock\RecordStockMovementAction;
use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\SatuanProduk;
use Illuminate\Support\Facades\DB;

class CreateSaleAction
{
    public function __construct(
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    /**
     * @param  array<int, array{satuan_id: int, qty: int}>  $items
     */
    public function execute(Pelanggan $pelanggan, array $items, PaymentMethod $metode): Penjualan
    {
        return DB::transaction(function () use ($pelanggan, $items, $metode) {
            $penjualan = Penjualan::create([
                'no_invoice' => $this->generateNomor(),
                'pelanggan_id' => $pelanggan->id,
                'tanggal' => now(),
                'metode_bayar' => $metode,
                'status_kirim' => DeliveryStatus::SiapKirim,
                'total' => 0,
            ]);

            foreach ($items as $item) {
                $satuan = SatuanProduk::with('produk')->findOrFail($item['satuan_id']);
                $qty = (int) $item['qty'];
                $qtyBase = $qty * $satuan->konversi;
                $hargaSatuan = (float) $satuan->harga_jual; // snapshot

                $penjualan->details()->create([
                    'produk_id' => $satuan->produk_id,
                    'satuan_id' => $satuan->id,
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $qty * $hargaSatuan,
                ]);

                $this->recordMovement->execute(
                    produk: $satuan->produk,
                    tipe: StockMovementType::Keluar,
                    qtyBase: $qtyBase,
                    referensi: "penjualan:{$penjualan->id}",
                );
            }

            $penjualan->update(['total' => $penjualan->details()->sum('subtotal')]);

            return $penjualan;
        });
    }

    private function generateNomor(): string
    {
        $prefix = 'INV-'.now()->format('Ymd');
        $last = Penjualan::where('no_invoice', 'like', $prefix.'%')
            ->orderByDesc('no_invoice')
            ->value('no_invoice');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
