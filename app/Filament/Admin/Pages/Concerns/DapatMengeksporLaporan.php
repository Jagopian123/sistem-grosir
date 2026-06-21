<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages\Concerns;

use App\Enums\FormatExport;
use App\Jobs\GenerateLaporanExportJob;
use App\Support\Laporan\DefinisiLaporan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Menambahkan tombol "Export Excel" & "Export PDF" pada halaman laporan.
 * Hanya id baris hasil filter saat ini yang dikirim ke queue; render berkas
 * dikerjakan job di latar belakang (bagian 6 butir 7) lalu pengguna menerima
 * notifikasi berisi tautan unduh.
 */
trait DapatMengeksporLaporan
{
    abstract protected function definisiExport(): DefinisiLaporan;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn () => $this->prosesExport(FormatExport::Excel)),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(fn () => $this->prosesExport(FormatExport::Pdf)),
        ];
    }

    protected function prosesExport(FormatExport $format): void
    {
        // Ambil id baris yang lolos filter aktif; honor filter tanpa
        // menduplikasi logikanya di job. Nama kolom di-qualify agar aman dari
        // ambiguitas pada laporan ber-join (laba kotor, produk terlaris).
        $query = $this->getFilteredTableQuery();
        $ids = $query === null
            ? []
            : array_map(intval(...), $query->pluck($query->getModel()->getQualifiedKeyName())->all());

        if ($ids === []) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Tidak ada baris yang cocok dengan filter saat ini untuk diekspor.')
                ->warning()
                ->send();

            return;
        }

        GenerateLaporanExportJob::dispatch(
            $this->definisiExport()::class,
            $format,
            $ids,
            (int) auth()->id(),
        );

        Notification::make()
            ->title('Sedang diproses')
            ->body('Export '.$format->label().' sedang dibuat di latar belakang. Notifikasi tautan unduh akan muncul saat selesai.')
            ->success()
            ->send();
    }
}
