<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $no_retur
 * @property int $pembelian_id
 * @property Carbon $tanggal
 * @property string $total
 * @property string|null $catatan
 */
class ReturPembelian extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'no_retur',
        'pembelian_id',
        'tanggal',
        'total',
        'catatan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Pembelian, $this> */
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    /** @return HasMany<DetailReturPembelian, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(DetailReturPembelian::class);
    }
}
