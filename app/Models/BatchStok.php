<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpiryStatus;
use Carbon\CarbonInterface;
use Database\Factories\BatchStokFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $produk_id
 * @property string|null $kode_batch
 * @property CarbonInterface|null $tanggal_kadaluarsa
 * @property int $qty_masuk
 * @property int $qty_sisa
 * @property string $sumber
 * @property CarbonInterface $tanggal_masuk
 */
class BatchStok extends Model
{
    /** @use HasFactory<BatchStokFactory> */
    use HasFactory;

    /** Ambang hari sebelum ED untuk memunculkan peringatan "mendekati". */
    public const HARI_PERINGATAN = 30;

    protected $fillable = [
        'produk_id',
        'kode_batch',
        'tanggal_kadaluarsa',
        'qty_masuk',
        'qty_sisa',
        'sumber',
        'tanggal_masuk',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal_kadaluarsa' => 'date',
            'tanggal_masuk' => 'date',
            'qty_masuk' => 'integer',
            'qty_sisa' => 'integer',
        ];
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /** Batch yang masih punya sisa stok. */
    /** @param Builder<BatchStok> $query */
    public function scopeTersisa(Builder $query): void
    {
        $query->where('qty_sisa', '>', 0);
    }

    /**
     * Batch bersisa yang ED-nya sudah lewat atau mendekati (dalam HARI_PERINGATAN
     * hari ke depan). Batch tanpa tanggal kadaluarsa tidak diikutkan.
     */
    /** @param Builder<BatchStok> $query */
    public function scopePerluPerhatian(Builder $query): void
    {
        $query->where('qty_sisa', '>', 0)
            ->whereNotNull('tanggal_kadaluarsa')
            ->whereDate('tanggal_kadaluarsa', '<=', now()->addDays(self::HARI_PERINGATAN));
    }

    public function status(): ExpiryStatus
    {
        if ($this->tanggal_kadaluarsa === null) {
            return ExpiryStatus::Aman;
        }

        if ($this->tanggal_kadaluarsa->isPast()) {
            return ExpiryStatus::Kadaluarsa;
        }

        if ($this->tanggal_kadaluarsa->lessThanOrEqualTo(now()->addDays(self::HARI_PERINGATAN))) {
            return ExpiryStatus::Mendekati;
        }

        return ExpiryStatus::Aman;
    }

    /** Sisa hari menuju kadaluarsa; negatif bila sudah lewat, null bila tak ada ED. */
    public function sisaHari(): ?int
    {
        if ($this->tanggal_kadaluarsa === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->tanggal_kadaluarsa->startOfDay(), false);
    }
}
