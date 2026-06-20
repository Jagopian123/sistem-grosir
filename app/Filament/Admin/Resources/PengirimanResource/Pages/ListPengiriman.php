<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PengirimanResource\Pages;

use App\Filament\Admin\Resources\PengirimanResource;
use Filament\Resources\Pages\ListRecords;

class ListPengiriman extends ListRecords
{
    protected static string $resource = PengirimanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
