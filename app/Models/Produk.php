<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\SearchableFullText;
use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory;

    use RecordsActivity;
    use SearchableFullText;

    protected $fillable = [
        'kategori_id',
        'nama',
        'satuan_dasar',
        'stok',
        'stok_min',
        'harga_beli',
        'aktif',
        'lacak_kadaluarsa',
    ];

    /** @return list<string> */
    public function fullTextColumns(): array
    {
        return ['nama'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stok' => 'integer',
            'stok_min' => 'integer',
            'harga_beli' => 'decimal:2',
            'aktif' => 'boolean',
            'lacak_kadaluarsa' => 'boolean',
        ];
    }

    /** @return BelongsTo<Kategori, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /** @return HasMany<SatuanProduk, $this> */
    public function satuanProduk(): HasMany
    {
        return $this->hasMany(SatuanProduk::class);
    }

    /** @return HasMany<MutasiStok, $this> */
    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class);
    }

    /** @return HasMany<BatchStok, $this> */
    public function batchStok(): HasMany
    {
        return $this->hasMany(BatchStok::class);
    }

    /** @return HasMany<DetailPenjualan, $this> */
    public function detailPenjualan(): HasMany
    {
        return $this->hasMany(DetailPenjualan::class);
    }

    /** @return HasMany<DetailPembelian, $this> */
    public function detailPembelian(): HasMany
    {
        return $this->hasMany(DetailPembelian::class);
    }

    /** @param Builder<Produk> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /** Stok sama dengan atau di bawah stok minimum */
    /** @param Builder<Produk> $query */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('stok', '<=', 'stok_min');
    }

    /** Produk yang dilacak tanggal kadaluarsa/batch-nya */
    /** @param Builder<Produk> $query */
    public function scopeLacakKadaluarsa(Builder $query): void
    {
        $query->where('lacak_kadaluarsa', true);
    }
}
