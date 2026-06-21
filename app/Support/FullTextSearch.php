<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pembantu pencarian FULLTEXT (bagian 6: search harus cepat).
 *
 * MySQL/MariaDB punya FULLTEXT index di kolom yang dicari (mis. produks.nama).
 * Kelas ini menyiapkan term untuk MATCH ... AGAINST (BOOLEAN MODE) dan memutuskan
 * kapan FULLTEXT layak dipakai. Driver lain (mis. SQLite saat test) tidak didukung,
 * jadi pemanggil harus fallback ke LIKE.
 */
class FullTextSearch
{
    /** Selaras default innodb_ft_min_token_size MySQL: kata < 3 huruf tidak terindeks. */
    public const int MIN_TOKEN_SIZE = 3;

    /** @var list<string> Driver yang mendukung MATCH ... AGAINST. */
    private const array SUPPORTED_DRIVERS = ['mysql', 'mariadb'];

    /** Apakah koneksi mendukung FULLTEXT MATCH ... AGAINST. */
    public static function isSupported(?string $connection = null): bool
    {
        return in_array(DB::connection($connection)->getDriverName(), self::SUPPORTED_DRIVERS, true);
    }

    /**
     * Apakah term layak dicari via FULLTEXT.
     *
     * Token lebih pendek dari MIN_TOKEN_SIZE tidak ada di index, jadi pencarian
     * BOOLEAN MODE wajib (+token*) akan kosong. Untuk kasus itu pemanggil sebaiknya
     * fallback ke LIKE agar "search as you type" tetap memberi hasil.
     */
    public static function qualifies(string $term): bool
    {
        $tokens = self::tokenize($term);

        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if (mb_strlen($token) < self::MIN_TOKEN_SIZE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ubah input bebas jadi term BOOLEAN MODE: tiap kata wajib ada & cocok sebagai prefix.
     *
     * "beras putih" => "+beras* +putih*" (cocok "Beras Putih Premium").
     */
    public static function booleanTerm(string $term): string
    {
        return collect(self::tokenize($term))
            ->map(static fn (string $token): string => '+'.$token.'*')
            ->implode(' ');
    }

    /**
     * Pecah jadi token bersih: karakter operator boolean fulltext dibuang supaya
     * tidak menimbulkan error sintaks maupun jadi celah injeksi.
     *
     * @return list<string>
     */
    private static function tokenize(string $term): array
    {
        $clean = preg_replace('/[+\-><()~*"@]+/u', ' ', $term) ?? '';

        return array_values(array_filter(
            preg_split('/\s+/u', trim($clean)) ?: [],
            static fn (string $token): bool => $token !== '',
        ));
    }
}
