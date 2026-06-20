<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailReturPembelian extends Model
{
    protected $fillable = [
        'retur_pembelian_id',
        'produk_id',
        'qty',
        'harga_beli',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_beli' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ReturPembelian, $this> */
    public function returPembelian(): BelongsTo
    {
        return $this->belongsTo(ReturPembelian::class);
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
