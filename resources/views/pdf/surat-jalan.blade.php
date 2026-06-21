<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Surat Jalan — {{ $penjualan->no_invoice }}</title>
    @include('pdf.partials.surat-jalan-style')
</head>
<body>
<div class="page">
    @include('pdf.partials.surat-jalan-body')
</div>
</body>
</html>
