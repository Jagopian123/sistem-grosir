<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\SatuanProdukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatuanProduk extends Model
{
    /** @use HasFactory<SatuanProdukFactory> */
    use HasFactory;

    use RecordsActivity;

    protected $fillable = [
        'produk_id',
        'nama_satuan',
        'konversi',
        'harga_jual',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'konversi' => 'integer',
            'harga_jual' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Produk, $this> */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }
}
