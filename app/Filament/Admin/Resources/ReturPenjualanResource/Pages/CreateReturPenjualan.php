<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ReturPenjualanResource\Pages;

use App\Actions\Sales\CreateSalesReturnAction;
use App\Filament\Admin\Resources\ReturPenjualanResource;
use App\Models\Penjualan;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateReturPenjualan extends CreateRecord
{
    protected static string $resource = ReturPenjualanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $penjualan = Penjualan::findOrFail($data['penjualan_id']);
        $items = $data['items'] ?? [];

        try {
            $retur = app(CreateSalesReturnAction::class)->execute(
                penjualan: $penjualan,
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
            ->title('Retur penjualan berhasil dicatat')
            ->body("No. {$retur->no_retur} — stok dikembalikan, mutasi tercatat.")
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
