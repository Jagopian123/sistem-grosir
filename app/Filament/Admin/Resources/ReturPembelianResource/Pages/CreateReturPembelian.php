<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPembelianResource\Pages;

use App\Actions\Purchasing\CreatePurchaseReturnAction;
use App\Filament\Admin\Resources\ReturPembelianResource;
use App\Models\Pembelian;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateReturPembelian extends CreateRecord
{
    protected static string $resource = ReturPembelianResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $pembelian = Pembelian::findOrFail($data['pembelian_id']);
        $items = $data['items'] ?? [];

        try {
            $retur = app(CreatePurchaseReturnAction::class)->execute(
                pembelian: $pembelian,
                items: $items,
                tanggal: Carbon::parse($data['tanggal']),
                catatan: $data['catatan'] ?? null,
            );
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Retur gagal diproses')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }

        Notification::make()
            ->title('Retur pembelian berhasil dicatat')
            ->body("No. {$retur->no_retur} — stok dikurangi, mutasi tercatat.")
            ->success()
            ->send();

        return $retur;
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
