<?php

declare(strict_types=1);

namespace App\Support\Laporan;

use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Menulis laporan ke berkas XLSX memakai OpenSpout (streaming, hemat memori).
 * Nilai uang/angka ditulis sebagai numerik agar bisa dijumlah di spreadsheet.
 */
class PenulisExcelLaporan
{
    public function tulis(DefinisiLaporan $definisi, Builder $query, string $pathAbsolut): void
    {
        $kolom = $definisi->kolom();

        $writer = new Writer;
        $writer->openToFile($pathAbsolut);

        $headerStyle = (new Style)->setFontBold();
        $writer->addRow(Row::fromValues(
            array_map(fn (KolomLaporan $k): string => $k->label, $kolom),
            $headerStyle,
        ));

        // cursor(): satu query streaming, hemat memori meski baris banyak.
        foreach ($query->cursor() as $record) {
            $writer->addRow(Row::fromValues(
                array_map(fn (KolomLaporan $k): mixed => $k->nilaiExcel($record), $kolom),
            ));
        }

        $writer->close();
    }
}
