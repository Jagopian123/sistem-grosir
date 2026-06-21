<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\FormatExport;
use App\Models\User;
use App\Support\Laporan\DefinisiLaporan;
use App\Support\Laporan\PenulisExcelLaporan;
use App\Support\Laporan\PenulisPdfLaporan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Membuat berkas export laporan (Excel/PDF) di background dari daftar id hasil
 * filter, simpan ke storage, lalu beri tahu pengguna lewat notifikasi database
 * + tautan unduh bertanda tangan. Render/penulisan berkas adalah pekerjaan berat
 * yang sengaja dipindah ke queue agar tidak memblokir request — bagian 6 butir 7.
 */
class GenerateLaporanExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Coba ulang bila gagal sementara (mis. Redis sibuk). */
    public int $tries = 3;

    /** Export besar bisa lama; beri ruang sebelum dianggap timeout. */
    public int $timeout = 300;

    /** Jeda antar percobaan (detik). */
    public int $backoff = 30;

    private const DISK = 'local';

    private const DIREKTORI = 'laporan';

    /**
     * @param  class-string<DefinisiLaporan>  $definisiClass  Jenis laporan yang diekspor
     * @param  list<int>  $ids  Primary key baris hasil filter di tabel laporan
     * @param  int  $userId  Penerima notifikasi saat berkas siap
     */
    public function __construct(
        private string $definisiClass,
        private FormatExport $format,
        private array $ids,
        private int $userId,
    ) {}

    public function handle(PenulisExcelLaporan $excel, PenulisPdfLaporan $pdf): void
    {
        $user = User::find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        $definisi = app($this->definisiClass);

        if (! $definisi instanceof DefinisiLaporan) {
            $this->beriTahuGagal($user, 'Jenis laporan tidak dikenali.');

            return;
        }

        if ($this->ids === []) {
            $this->beriTahuGagal($user, 'Tidak ada data yang cocok dengan filter untuk diekspor.');

            return;
        }

        $query = $definisi->baseQuery()->whereKey($this->ids);

        $namaFile = $definisi->namaBerkas().'-'.now()->format('Ymd-His')
            .'-'.Str::lower((string) Str::ulid()).'.'.$this->format->ekstensi();

        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory(self::DIREKTORI);
        $relativePath = self::DIREKTORI.'/'.$namaFile;

        if ($this->format === FormatExport::Excel) {
            $excel->tulis($definisi, $query, $disk->path($relativePath));
        } else {
            $disk->put($relativePath, $pdf->render($definisi, $query));
        }

        $url = URL::temporarySignedRoute(
            'unduhan.laporan',
            now()->addDay(),
            ['file' => $namaFile],
        );

        Notification::make()
            ->title('Export laporan siap diunduh')
            ->body($definisi->judul().' ('.$this->format->label().') telah dibuat. Tautan berlaku 24 jam.')
            ->success()
            ->actions([
                Action::make('unduh')
                    ->label('Unduh '.$this->format->label())
                    ->url($url)
                    ->openUrlInNewTab(),
            ])
            ->sendToDatabase($user);
    }

    public function failed(Throwable $e): void
    {
        $user = User::find($this->userId);

        if ($user instanceof User) {
            $this->beriTahuGagal($user, 'Terjadi kesalahan saat membuat berkas export. Silakan coba lagi.');
        }
    }

    private function beriTahuGagal(User $user, string $pesan): void
    {
        Notification::make()
            ->title('Gagal membuat export laporan')
            ->body($pesan)
            ->danger()
            ->sendToDatabase($user);
    }
}
