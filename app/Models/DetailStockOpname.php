<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $stok_sistem
 * @property int $stok_fisik
 * @property int $selisih
 */
class DetailStockOpname extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'produk_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stok_sistem' => 'integer',
            'stok_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    /** @return BelongsTo<StockOpname, $this> */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
