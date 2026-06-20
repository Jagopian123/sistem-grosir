<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property StockMovementType $tipe
 * @property int $qty
 * @property int $stok_sebelum
 * @property int $stok_sesudah
 */
class MutasiStok extends Model
{
    protected $fillable = [
        'produk_id',
        'tipe',
        'qty',
        'referensi',
        'stok_sebelum',
        'stok_sesudah',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tipe' => StockMovementType::class,
            'qty' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
        ];
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
