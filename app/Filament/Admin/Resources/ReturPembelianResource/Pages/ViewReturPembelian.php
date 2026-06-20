<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPembelianResource\Pages;

use App\Filament\Admin\Resources\ReturPembelianResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReturPembelian extends ViewRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
