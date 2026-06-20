<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory;

    protected $fillable = ['nama'];

    /** @return HasMany<Produk, $this> */
    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class);
    }
}
