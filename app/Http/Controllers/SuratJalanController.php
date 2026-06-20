<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SuratJalanController extends Controller
{
    public function __invoke(Penjualan $penjualan): Response
    {
        $penjualan->loadMissing(['pelanggan', 'sopir', 'details.produk', 'details.satuan']);

        $pdf = Pdf::loadView('pdf.surat-jalan', compact('penjualan'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("surat-jalan-{$penjualan->no_invoice}.pdf");
    }
}
