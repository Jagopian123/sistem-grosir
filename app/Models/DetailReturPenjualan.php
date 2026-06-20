<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailReturPenjualan extends Model
{
    protected $fillable = [
        'retur_penjualan_id',
        'produk_id',
        'satuan_id',
        'qty',
        'harga_satuan',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ReturPenjualan, $this> */
    public function returPenjualan(): BelongsTo
    {
        return $this->belongsTo(ReturPenjualan::class);
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    /** @return BelongsTo<SatuanProduk, $this> */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(SatuanProduk::class, 'satuan_id');
    }
}
