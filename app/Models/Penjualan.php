<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Models\Concerns\RecordsActivity;
use Carbon\Carbon;
use Database\Factories\PenjualanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $no_invoice
 * @property int $pelanggan_id
 * @property int|null $sopir_id
 * @property Carbon $tanggal
 * @property string $subtotal
 * @property DiscountType|null $diskon_tipe
 * @property string $diskon_nilai
 * @property string $diskon_nominal
 * @property string $total
 * @property PaymentMethod $metode_bayar
 * @property DeliveryStatus $status_kirim
 * @property string|null $catatan
 */
class Penjualan extends Model
{
    /** @use HasFactory<PenjualanFactory> */
    use HasFactory;

    use RecordsActivity;

    protected $fillable = [
        'no_invoice',
        'pelanggan_id',
        'sopir_id',
        'tanggal',
        'subtotal',
        'diskon_tipe',
        'diskon_nilai',
        'diskon_nominal',
        'total',
        'metode_bayar',
        'status_kirim',
        'catatan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'subtotal' => 'decimal:2',
            'diskon_tipe' => DiscountType::class,
            'diskon_nilai' => 'decimal:2',
            'diskon_nominal' => 'decimal:2',
            'total' => 'decimal:2',
            'metode_bayar' => PaymentMethod::class,
            'status_kirim' => DeliveryStatus::class,
        ];
    }

    /** @return BelongsTo<Pelanggan, $this> */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class);
    }

    /** @return BelongsTo<Sopir, $this> */
    public function sopir(): BelongsTo
    {
        return $this->belongsTo(Sopir::class);
    }

    /** @return HasMany<DetailPenjualan, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /**
     * Apakah nota ini punya diskon yang efektif (potongan rupiah > 0).
     */
    public function adaDiskon(): bool
    {
        return $this->diskon_tipe !== null && (float) $this->diskon_nominal > 0;
    }

    /**
     * Label diskon untuk ditampilkan, mis. "Diskon (5%)" atau "Diskon".
     */
    public function labelDiskon(): string
    {
        if ($this->diskon_tipe === DiscountType::Persen) {
            return 'Diskon ('.rtrim(rtrim(number_format((float) $this->diskon_nilai, 2, '.', ''), '0'), '.').'%)';
        }

        return 'Diskon';
    }
}
