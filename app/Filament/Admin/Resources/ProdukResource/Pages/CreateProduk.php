<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProdukResource\Pages;

use App\Filament\Admin\Resources\ProdukResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduk extends CreateRecord
{
    protected static string $resource = ProdukResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
