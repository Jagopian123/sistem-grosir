<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\FullTextSearch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian instan lewat FULLTEXT index (bagian 6).
 *
 * Model yang memakai trait ini menyebut kolom ber-FULLTEXT di properti
 * $fullTextColumns. Scope whereFullTextSearch() memakai MATCH ... AGAINST
 * (BOOLEAN MODE) di MySQL/MariaDB sehingga search memakai index — bukan
 * `LIKE '%term%'` yang tak terindeks. Driver tanpa FULLTEXT (mis. SQLite saat
 * test) dan term terlalu pendek otomatis fallback ke LIKE.
 */
trait SearchableFullText
{
    /**
     * Kolom ber-FULLTEXT index. Di-override di model pemakai.
     *
     * @return list<string>
     */
    public function fullTextColumns(): array
    {
        return [];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWhereFullTextSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        $columns = $this->fullTextColumns();

        if ($term === '' || $columns === []) {
            return;
        }

        if (FullTextSearch::isSupported($this->getConnectionName()) && FullTextSearch::qualifies($term)) {
            $query->whereFullText($columns, FullTextSearch::booleanTerm($term), ['mode' => 'boolean']);

            return;
        }

        // Fallback: driver tanpa FULLTEXT atau term pendek (< MIN_TOKEN_SIZE).
        $query->where(function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
