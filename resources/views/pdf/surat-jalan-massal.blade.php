<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Surat Jalan (Massal) — {{ $penjualans->count() }} dokumen</title>
    @include('pdf.partials.surat-jalan-style')
</head>
<body>
@foreach($penjualans as $penjualan)
    {{-- Setiap surat jalan satu halaman; halaman terakhir tanpa page-break. --}}
    <div class="page @unless($loop->last) page-break @endunless">
        @include('pdf.partials.surat-jalan-body')
    </div>
@endforeach
</body>
</html>
