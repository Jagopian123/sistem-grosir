<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SopirResource\Pages;

use App\Filament\Admin\Resources\SopirResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSopir extends EditRecord
{
    protected static string $resource = SopirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
