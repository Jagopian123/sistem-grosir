<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Owner = 'Owner';
    case Admin = 'Admin';
    case Kasir = 'Kasir';
    case Gudang = 'Gudang';
    case Sopir = 'Sopir';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Kasir => 'Kasir',
            self::Gudang => 'Gudang',
            self::Sopir => 'Sopir',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
