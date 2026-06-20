<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SopirResource\Pages;

use App\Filament\Admin\Resources\SopirResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSopirs extends ListRecords
{
    protected static string $resource = SopirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Sopir'),
        ];
    }
}
