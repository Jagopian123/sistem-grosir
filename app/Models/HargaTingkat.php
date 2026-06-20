<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\HargaTingkatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tingkat harga per kuantitas untuk satu satuan produk.
 * Harga berlaku bila qty pembelian >= min_qty (tingkat tertinggi yang terpenuhi menang).
 */
class HargaTingkat extends Model
{
    /** @use HasFactory<HargaTingkatFactory> */
    use HasFactory;

    use RecordsActivity;

    protected $fillable = [
        'satuan_id',
        'min_qty',
        'harga',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'harga' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<SatuanProduk, $this> */
    public function satuan(): BelongsTo
    {
        return $this->belongsTo(SatuanProduk::class, 'satuan_id');
    }
}
