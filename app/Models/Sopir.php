<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopirFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sopir extends Model
{
    /** @use HasFactory<SopirFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'telepon',
    ];

    /** @return HasMany<Penjualan, $this> */
    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class);
    }
}
