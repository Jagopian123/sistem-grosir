<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penjualan>
 */
class PenjualanFactory extends Factory
{
    protected $model = Penjualan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        $total = $this->faker->numberBetween(50_000, 5_000_000);

        return [
            'no_invoice' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'pelanggan_id' => Pelanggan::factory(),
            'sopir_id' => null,
            'tanggal' => now(),
            'subtotal' => $total,
            'diskon_tipe' => null,
            'diskon_nilai' => 0,
            'diskon_nominal' => 0,
            'total' => $total,
            'metode_bayar' => PaymentMethod::Tunai,
            'status_kirim' => DeliveryStatus::SiapKirim,
            'catatan' => null,
        ];
    }

    public function dikirim(): static
    {
        return $this->state(['status_kirim' => DeliveryStatus::Dikirim]);
    }

    public function terkirim(): static
    {
        return $this->state(['status_kirim' => DeliveryStatus::Terkirim]);
    }
}
