<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPenjualanResource\Pages;

use App\Filament\Admin\Resources\ReturPenjualanResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReturPenjualan extends ViewRecord
{
    protected static string $resource = ReturPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
