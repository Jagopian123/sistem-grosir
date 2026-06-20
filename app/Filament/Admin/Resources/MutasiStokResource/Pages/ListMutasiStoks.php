<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MutasiStokResource\Pages;

use App\Filament\Admin\Resources\MutasiStokResource;
use Filament\Resources\Pages\ListRecords;

class ListMutasiStoks extends ListRecords
{
    protected static string $resource = MutasiStokResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
