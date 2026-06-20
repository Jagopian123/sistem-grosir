<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BatchStokResource\Pages;

use App\Filament\Admin\Resources\BatchStokResource;
use Filament\Resources\Pages\EditRecord;

class EditBatchStok extends EditRecord
{
    protected static string $resource = BatchStokResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
