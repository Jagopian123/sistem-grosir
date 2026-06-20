<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Struk — {{ $penjualan->no_invoice }}</title>
    @php
        // Font sedikit lebih besar di 80mm karena ruang lebih lega.
        $fs = $invoiceFormat->widthMm() >= 80 ? '9pt' : '8pt';
        $fsSmall = $invoiceFormat->widthMm() >= 80 ? '8pt' : '7pt';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans Mono', monospace; font-size: {{ $fs }}; color: #000; }
        .wrap { padding: 4px 6px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .toko { font-size: {{ $invoiceFormat->widthMm() >= 80 ? '12pt' : '10pt' }}; font-weight: bold; }
        .sub { font-size: {{ $fsSmall }}; }
        .hr { border-top: 1px dashed #000; margin: 4px 0; }
        .meta { width: 100%; border-collapse: collapse; font-size: {{ $fsSmall }}; }
        .meta td { padding: 1px 0; vertical-align: top; }
        .meta td.r { text-align: right; }
        .items { width: 100%; border-collapse: collapse; }
        .items td { padding: 1px 0; vertical-align: top; font-size: {{ $fs }}; }
        .qtyline td { font-size: {{ $fsSmall }}; }
        .tot { width: 100%; border-collapse: collapse; }
        .tot td { padding: 1px 0; }
        .tot td.r { text-align: right; }
        .grand { font-size: {{ $invoiceFormat->widthMm() >= 80 ? '11pt' : '9pt' }}; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="center">
        <div class="toko">{{ $toko }}</div>
        <div class="sub">Grosir Internal</div>
    </div>

    <div class="hr"></div>

    <table class="meta">
        <tr><td>No</td><td class="r">{{ $penjualan->no_invoice }}</td></tr>
        <tr><td>Tgl</td><td class="r">{{ $penjualan->tanggal->translatedFormat('d/m/Y H:i') }}</td></tr>
        <tr><td>Plgn</td><td class="r">{{ \Illuminate\Support\Str::limit($penjualan->pelanggan->nama_toko, 22) }}</td></tr>
        <tr><td>Bayar</td><td class="r">{{ $penjualan->metode_bayar->label() }}</td></tr>
    </table>

    <div class="hr"></div>

    <table class="items">
        @foreach($penjualan->details as $detail)
        <tr>
            <td colspan="2" class="bold">{{ $detail->produk->nama }}</td>
        </tr>
        <tr class="qtyline">
            <td>{{ $detail->qty }} {{ $detail->satuan->nama_satuan }} x {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}</td>
            <td class="right">{{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="hr"></div>

    <table class="tot">
        <tr class="grand">
            <td>TOTAL</td>
            <td class="r">Rp {{ number_format((float) $penjualan->total, 0, ',', '.') }}</td>
        </tr>
        <tr><td>Status</td><td class="r">LUNAS</td></tr>
    </table>

    @if($penjualan->catatan)
    <div class="hr"></div>
    <div class="sub">Catatan: {{ $penjualan->catatan }}</div>
    @endif

    <div class="hr"></div>

    <div class="center sub">
        Terima kasih<br>
        {{ now()->translatedFormat('d/m/Y H:i') }}
    </div>

</div>
</body>
</html>
