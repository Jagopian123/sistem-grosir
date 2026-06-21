<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unduh file dokumen yang dihasilkan job di background (mis. surat jalan massal).
 * Diamankan oleh middleware `signed` (URL bertanda tangan + kedaluwarsa) & `auth`.
 */
class UnduhanController extends Controller
{
    private const DISK = 'local';

    public function suratJalan(string $file): StreamedResponse
    {
        return $this->unduh('surat-jalan', $file);
    }

    public function laporan(string $file): StreamedResponse
    {
        return $this->unduh('laporan', $file);
    }

    private function unduh(string $direktori, string $file): StreamedResponse
    {
        // Cegah path traversal: hanya nama file polos yang diterima.
        if ($file !== basename($file)) {
            throw new NotFoundHttpException;
        }

        $path = $direktori.'/'.$file;
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            throw new NotFoundHttpException('Dokumen tidak ditemukan atau sudah dibersihkan.');
        }

        return $disk->download($path);
    }
}
