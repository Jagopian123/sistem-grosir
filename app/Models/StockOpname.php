<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $no_opname
 * @property Carbon $tanggal
 * @property int $total_selisih
 * @property string|null $catatan
 */
class StockOpname extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'no_opname',
        'tanggal',
        'total_selisih',
        'catatan',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'total_selisih' => 'integer',
        ];
    }

    /** @return HasMany<DetailStockOpname, $this> */
    public function details(): HasMany
    {
        return $this->hasMany(DetailStockOpname::class);
    }
}
