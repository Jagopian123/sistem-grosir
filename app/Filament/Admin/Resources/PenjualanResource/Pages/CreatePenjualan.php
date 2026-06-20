<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PenjualanResource\Pages;

use App\Actions\Sales\CreateSaleAction;
use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\PenjualanResource;
use App\Models\Pelanggan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $pelanggan = Pelanggan::findOrFail($data['pelanggan_id']);
        $metode = PaymentMethod::from($data['metode_bayar']);
        $items = $data['items'] ?? [];

        $penjualan = app(CreateSaleAction::class)->execute(
            pelanggan: $pelanggan,
            items: $items,
            metode: $metode,
        );

        Notification::make()
            ->title('Penjualan berhasil dicatat')
            ->body("No. {$penjualan->no_invoice} — Total Rp ".number_format((float) $penjualan->total, 0, ',', '.'))
            ->success()
            ->send();

        return $penjualan;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }
}
