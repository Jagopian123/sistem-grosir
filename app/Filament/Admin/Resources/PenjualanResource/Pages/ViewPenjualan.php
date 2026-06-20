<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PenjualanResource\Pages;

use App\Actions\Delivery\AssignDriverAction;
use App\Actions\Delivery\MarkDeliveredAction;
use App\Enums\DeliveryStatus;
use App\Filament\Admin\Resources\PenjualanResource;
use App\Models\Penjualan;
use App\Models\Sopir;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjualan extends ViewRecord
{
    protected static string $resource = PenjualanResource::class;

    public function getRecord(): Penjualan
    {
        $record = parent::getRecord();
        if (! $record instanceof Penjualan) {
            throw new \RuntimeException('Record bukan instance Penjualan.');
        }

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignSopir')
                ->label('Assign Sopir')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->visible(fn (): bool => $this->getRecord()->status_kirim === DeliveryStatus::SiapKirim)
                ->form([
                    Select::make('sopir_id')
                        ->label('Pilih Sopir')
                        ->options(Sopir::query()->orderBy('nama')->pluck('nama', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var Penjualan $penjualan */
                    $penjualan = $this->getRecord();
                    $sopir = Sopir::findOrFail($data['sopir_id']);

                    try {
                        app(AssignDriverAction::class)->execute($penjualan, $sopir);

                        Notification::make()
                            ->title('Sopir berhasil di-assign')
                            ->body("{$sopir->nama} ditetapkan. Status berubah ke Dikirim.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['sopir_id', 'status_kirim']);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Gagal assign sopir')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Assign Sopir')
                ->modalSubmitActionLabel('Assign'),

            Action::make('tandaiTerkirim')
                ->label('Tandai Terkirim')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->status_kirim === DeliveryStatus::Dikirim)
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Terkirim')
                ->modalDescription(fn (): string => "Tandai {$this->getRecord()->no_invoice} sebagai sudah terkirim ke pelanggan?")
                ->modalSubmitActionLabel('Ya, Sudah Terkirim')
                ->action(function (): void {
                    /** @var Penjualan $penjualan */
                    $penjualan = $this->getRecord();

                    try {
                        app(MarkDeliveredAction::class)->execute($penjualan);

                        Notification::make()
                            ->title('Status diperbarui ke Terkirim')
                            ->success()
                            ->send();

                        $this->refreshFormData(['status_kirim']);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Gagal memperbarui status')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cetakSuratJalan')
                ->label('Cetak Surat Jalan')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('surat-jalan', $this->getRecord()))
                ->openUrlInNewTab(),
        ];
    }
}
