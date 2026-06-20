<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceFormat;
use App\Models\Penjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    private const MM_TO_PT = 72 / 25.4;

    public function __invoke(Penjualan $penjualan, string $format = 'a4'): Response
    {
        $invoiceFormat = InvoiceFormat::tryFrom($format) ?? InvoiceFormat::A4;

        $penjualan->loadMissing(['pelanggan', 'sopir', 'details.produk', 'details.satuan']);

        $toko = config('app.name');

        if (! $invoiceFormat->isThermal()) {
            $pdf = Pdf::loadView('pdf.invoice', compact('penjualan', 'toko'))
                ->setPaper('a4', 'portrait');

            return $pdf->stream("invoice-{$penjualan->no_invoice}.pdf");
        }

        $pdf = Pdf::loadView('pdf.struk', compact('penjualan', 'toko', 'invoiceFormat'))
            ->setPaper($this->thermalPaper($invoiceFormat, $penjualan->details->count()));

        return $pdf->stream("struk-{$penjualan->no_invoice}.pdf");
    }

    /**
     * Ukuran kertas dinamis untuk struk thermal: lebar tetap (58/80mm),
     * tinggi diestimasi dari jumlah item agar tidak ada potongan/ruang kosong berlebih.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function thermalPaper(InvoiceFormat $format, int $itemCount): array
    {
        $heightMm = 100 + ($itemCount * 16);

        return [
            0,
            0,
            $format->widthMm() * self::MM_TO_PT,
            $heightMm * self::MM_TO_PT,
        ];
    }
}
