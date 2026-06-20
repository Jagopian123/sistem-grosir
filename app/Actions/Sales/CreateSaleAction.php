<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Actions\Stock\RecordStockMovementAction;
use App\Enums\DeliveryStatus;
use App\Enums\DiscountType;
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
     * Diskon bersifat manual per nota: murni potongan uang, tidak menyentuh stok/ledger.
     * Harga item tetap di-"foto" apa adanya; diskon disimpan terpisah agar nota & laporan akurat.
     *
     * @param  array<int, array{satuan_id: int, qty: int}>  $items
     * @param  float  $diskonNilai  Persen (0–100) bila tipe Persen, atau rupiah bila tipe Nominal.
     */
    public function execute(
        Pelanggan $pelanggan,
        array $items,
        PaymentMethod $metode,
        ?DiscountType $diskonTipe = null,
        float $diskonNilai = 0,
    ): Penjualan {
        return DB::transaction(function () use ($pelanggan, $items, $metode, $diskonTipe, $diskonNilai) {
            $penjualan = Penjualan::create([
                'no_invoice' => $this->generateNomor(),
                'pelanggan_id' => $pelanggan->id,
                'tanggal' => now(),
                'subtotal' => 0,
                'total' => 0,
                'metode_bayar' => $metode,
                'status_kirim' => DeliveryStatus::SiapKirim,
            ]);

            foreach ($items as $item) {
                $satuan = SatuanProduk::with(['produk', 'hargaTingkat'])->findOrFail($item['satuan_id']);
                $qty = (int) $item['qty'];
                $qtyBase = $qty * $satuan->konversi;
                $hargaSatuan = $satuan->hargaUntukQty($qty); // harga bertingkat per qty, di-"foto"

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

            $subtotal = (float) $penjualan->details()->sum('subtotal');
            [$tipe, $nilai, $nominal] = $this->hitungDiskon($subtotal, $diskonTipe, $diskonNilai);

            $penjualan->update([
                'subtotal' => $subtotal,
                'diskon_tipe' => $tipe,
                'diskon_nilai' => $nilai,
                'diskon_nominal' => $nominal,
                'total' => $subtotal - $nominal,
            ]);

            return $penjualan;
        });
    }

    /**
     * Hitung potongan rupiah final dari subtotal. Persen dibatasi 0–100,
     * nominal dibatasi tidak melebihi subtotal (total tidak boleh negatif).
     *
     * @return array{0: ?DiscountType, 1: float, 2: float}
     */
    private function hitungDiskon(float $subtotal, ?DiscountType $tipe, float $nilai): array
    {
        if ($tipe === null || $nilai <= 0) {
            return [null, 0.0, 0.0];
        }

        $nominal = match ($tipe) {
            DiscountType::Persen => $subtotal * min($nilai, 100.0) / 100,
            DiscountType::Nominal => $nilai,
        };

        $nominal = round(max(0.0, min($nominal, $subtotal)), 2);

        return [$tipe, $nilai, $nominal];
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
