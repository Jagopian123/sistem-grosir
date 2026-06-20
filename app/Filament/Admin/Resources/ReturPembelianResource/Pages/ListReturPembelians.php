<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPembelianResource\Pages;

use App\Filament\Admin\Resources\ReturPembelianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturPembelians extends ListRecords
{
    protected static string $resource = ReturPembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Retur'),
        ];
    }
}
