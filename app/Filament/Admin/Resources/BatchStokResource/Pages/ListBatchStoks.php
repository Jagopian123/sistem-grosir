<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BatchStokResource\Pages;

use App\Filament\Admin\Resources\BatchStokResource;
use Filament\Resources\Pages\ListRecords;

class ListBatchStoks extends ListRecords
{
    protected static string $resource = BatchStokResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
