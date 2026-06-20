<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PenjualanResource\Pages;

use App\Filament\Admin\Resources\PenjualanResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
