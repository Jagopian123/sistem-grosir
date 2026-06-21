<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Support\SuratJalanPdf;
use Illuminate\Http\Response;

class SuratJalanController extends Controller
{
    public function __invoke(Penjualan $penjualan, SuratJalanPdf $pdf): Response
    {
        return $pdf->satu($penjualan)
            ->stream("surat-jalan-{$penjualan->no_invoice}.pdf");
    }
}
