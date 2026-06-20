<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SopirResource\Pages;

use App\Filament\Admin\Resources\SopirResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSopir extends CreateRecord
{
    protected static string $resource = SopirResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
