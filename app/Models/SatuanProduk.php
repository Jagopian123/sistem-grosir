<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\SatuanProdukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** @return HasMany<HargaTingkat, $this> */
    public function hargaTingkat(): HasMany
    {
        return $this->hasMany(HargaTingkat::class, 'satuan_id')->orderBy('min_qty');
    }

    /**
     * Harga jual per unit untuk qty tertentu.
     * Memakai tingkat harga tertinggi yang min_qty-nya terpenuhi; bila tak ada,
     * jatuh ke harga_jual dasar. Relasi hargaTingkat harus sudah di-eager load
     * (lihat preventLazyLoading di lokal).
     */
    public function hargaUntukQty(int $qty): float
    {
        $tingkat = $this->hargaTingkat
            ->sortByDesc('min_qty')
            ->first(fn (HargaTingkat $t): bool => $t->min_qty <= $qty);

        return (float) ($tingkat instanceof HargaTingkat ? $tingkat->harga : $this->harga_jual);
    }
}
