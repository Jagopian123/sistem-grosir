<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StockOpnameResource\Pages;

use App\Filament\Admin\Resources\StockOpnameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Opname'),
        ];
    }
}
