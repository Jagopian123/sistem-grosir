<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PembelianResource\Pages;

use App\Actions\Purchasing\ReceiveStockAction;
use App\Filament\Admin\Resources\PembelianResource;
use App\Models\Supplier;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $supplier = Supplier::findOrFail($data['supplier_id']);
        $items = $data['items'] ?? [];

        $pembelian = app(ReceiveStockAction::class)->execute(
            supplier: $supplier,
            items: $items,
            tanggal: Carbon::parse($data['tanggal']),
        );

        Notification::make()
            ->title('Stok masuk berhasil dicatat')
            ->body("No. {$pembelian->no_pembelian} — {$pembelian->details()->count()} item")
            ->success()
            ->send();

        return $pembelian;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null; // notifikasi sudah dikirim manual di handleRecordCreation
    }
}
