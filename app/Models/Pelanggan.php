<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\PelangganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    /** @use HasFactory<PelangganFactory> */
    use HasFactory;

    use RecordsActivity;

    protected $fillable = [
        'nama_toko',
        'nama_kontak',
        'telepon',
        'alamat',
    ];

    /** @return HasMany<Penjualan, $this> */
    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class);
    }
}
