<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Penjualan;
use App\Models\User;
use App\Support\SuratJalanPdf;
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
 * Merender banyak Surat Jalan jadi satu PDF di background, simpan ke storage,
 * lalu beri tahu pengguna lewat notifikasi database + tautan unduh saat siap.
 * Pekerjaan berat (render dompdf) tidak memblokir request — sesuai bagian 6 butir 7.
 */
class GenerateBulkSuratJalanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Coba ulang bila gagal sementara (mis. Redis sibuk). */
    public int $tries = 3;

    /** Render PDF besar bisa lama; beri ruang sebelum dianggap timeout. */
    public int $timeout = 300;

    /** Jeda antar percobaan (detik). */
    public int $backoff = 30;

    private const DISK = 'local';

    private const DIREKTORI = 'surat-jalan';

    /**
     * @param  list<int>  $penjualanIds  ID penjualan yang akan dicetak surat jalannya
     * @param  int  $userId  Penerima notifikasi saat dokumen siap
     */
    public function __construct(
        private array $penjualanIds,
        private int $userId,
    ) {}

    public function handle(SuratJalanPdf $pdf): void
    {
        $user = User::find($this->userId);

        if (! $user instanceof User) {
            return;
        }

        $penjualans = Penjualan::query()
            ->whereIn('id', $this->penjualanIds)
            ->orderBy('tanggal')
            ->get();

        if ($penjualans->isEmpty()) {
            $this->beriTahuGagal($user, 'Tidak ada pengiriman yang cocok untuk dicetak.');

            return;
        }

        $isi = $pdf->banyak($penjualans);

        $namaFile = 'surat-jalan-massal-'.now()->format('Ymd-His').'-'.Str::lower((string) Str::ulid()).'.pdf';
        Storage::disk(self::DISK)->put(self::DIREKTORI.'/'.$namaFile, $isi);

        $url = URL::temporarySignedRoute(
            'unduhan.surat-jalan',
            now()->addDay(),
            ['file' => $namaFile],
        );

        Notification::make()
            ->title('Surat jalan massal siap diunduh')
            ->body($penjualans->count().' surat jalan telah digabung menjadi satu PDF. Tautan berlaku 24 jam.')
            ->success()
            ->actions([
                Action::make('unduh')
                    ->label('Unduh PDF')
                    ->url($url)
                    ->openUrlInNewTab(),
            ])
            ->sendToDatabase($user);
    }

    public function failed(Throwable $e): void
    {
        $user = User::find($this->userId);

        if ($user instanceof User) {
            $this->beriTahuGagal($user, 'Terjadi kesalahan saat membuat dokumen. Silakan coba lagi.');
        }
    }

    private function beriTahuGagal(User $user, string $pesan): void
    {
        Notification::make()
            ->title('Gagal membuat surat jalan massal')
            ->body($pesan)
            ->danger()
            ->sendToDatabase($user);
    }
}
