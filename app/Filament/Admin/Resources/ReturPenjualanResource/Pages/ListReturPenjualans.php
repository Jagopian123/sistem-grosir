<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPenjualanResource\Pages;

use App\Filament\Admin\Resources\ReturPenjualanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturPenjualans extends ListRecords
{
    protected static string $resource = ReturPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Retur'),
        ];
    }
}
