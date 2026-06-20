<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\StockOpnameResource\Pages;

use App\Actions\Stock\StockOpnameAction;
use App\Filament\Admin\Resources\StockOpnameResource;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];

        try {
            $opname = app(StockOpnameAction::class)->execute(
                items: $items,
                tanggal: Carbon::parse($data['tanggal']),
                catatan: $data['catatan'] ?? null,
            );
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Opname gagal diproses')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }

        Notification::make()
            ->title('Stock opname berhasil dicatat')
            ->body("No. {$opname->no_opname} — selisih bersih {$opname->total_selisih}, penyesuaian tercatat.")
            ->success()
            ->send();

        return $opname;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null; // notifikasi dikirim manual di handleRecordCreation
    }
}
