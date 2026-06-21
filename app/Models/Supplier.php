<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\SearchableFullText;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    use RecordsActivity;
    use SearchableFullText;

    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
    ];

    /** @return list<string> */
    public function fullTextColumns(): array
    {
        return ['nama'];
    }

    /** @return HasMany<Pembelian, $this> */
    public function pembelian(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }
}
