<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    protected $fillable = [
        'no_invoice',
        'pelanggan_id',
        'sopir_id',
        'tanggal',
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
}
