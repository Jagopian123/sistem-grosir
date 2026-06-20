<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Mencatat perubahan atribut model ke activity log (audit trail).
 *
 * Model bisa membatasi kolom yang diaudit lewat properti `$activityLogAttributes`;
 * jika tidak diset, semua kolom `fillable` diaudit. Hanya kolom yang benar-benar
 * berubah yang dicatat, dan log kosong tidak disimpan.
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        /** @var list<string> $attributes */
        $attributes = property_exists($this, 'activityLogAttributes')
            ? $this->activityLogAttributes
            : $this->getFillable();

        return LogOptions::defaults()
            ->logOnly($attributes)
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
