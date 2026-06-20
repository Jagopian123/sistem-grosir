<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Invoice — {{ $penjualan->no_invoice }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #1a1a1a; }

        .page { padding: 24px 32px; }

        .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 10px; margin-bottom: 14px; }
        .header-inner { width: 100%; }
        .toko-nama { font-size: 16pt; font-weight: bold; }
        .toko-sub { font-size: 9pt; color: #555; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title p { font-size: 9pt; color: #555; margin-top: 2px; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta-table td { padding: 3px 0; font-size: 10pt; vertical-align: top; }
        .meta-label { width: 130px; color: #555; }
        .meta-sep { width: 10px; }

        .box { border: 1px solid #ccc; border-radius: 4px; padding: 10px 14px; margin-bottom: 14px; }
        .box-title { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #777; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .box-content { font-size: 10pt; }
        .box-content .nama { font-weight: bold; font-size: 11pt; }
        .box-content .sub { color: #444; margin-top: 2px; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .items-table th { background: #f0f0f0; font-size: 9pt; text-align: left; padding: 6px 8px; border: 1px solid #ccc; }
        .items-table td { font-size: 10pt; padding: 6px 8px; border: 1px solid #ccc; vertical-align: top; }
        .items-table tr:nth-child(even) td { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row td { font-weight: bold; background: #f0f0f0 !important; border-top: 2px solid #aaa; }

        .pay-info { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .pay-info td { vertical-align: top; }
        .pay-left { width: 60%; padding-right: 12px; }
        .pay-right { width: 40%; }
        .badge-lunas { display: inline-block; border: 2px solid #16a34a; color: #16a34a; font-weight: bold; font-size: 13pt; text-transform: uppercase; letter-spacing: 2px; padding: 6px 18px; border-radius: 4px; }

        .note { font-size: 9pt; color: #555; border-top: 1px dashed #ccc; padding-top: 8px; margin-top: 12px; }
        .footer-note { text-align: center; font-size: 9pt; color: #777; margin-top: 24px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <table class="header-inner">
            <tr>
                <td>
                    <div class="toko-nama">{{ $toko }}</div>
                    <div class="toko-sub">Sistem Manajemen Grosir Internal</div>
                </td>
                <td class="doc-title">
                    <h2>Invoice</h2>
                    <p>No: {{ $penjualan->no_invoice }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Meta info --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-sep">:</td>
            <td>{{ $penjualan->tanggal->translatedFormat('d F Y H:i') }}</td>
            <td class="meta-label">Metode Bayar</td>
            <td class="meta-sep">:</td>
            <td><strong>{{ $penjualan->metode_bayar->label() }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Status Kirim</td>
            <td class="meta-sep">:</td>
            <td>{{ $penjualan->status_kirim->label() }}</td>
            <td class="meta-label">Dicetak</td>
            <td class="meta-sep">:</td>
            <td>{{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
    </table>

    {{-- Pelanggan --}}
    <div class="box">
        <div class="box-title">Ditagihkan Kepada</div>
        <div class="box-content">
            <div class="nama">{{ $penjualan->pelanggan->nama_toko }}</div>
            @if($penjualan->pelanggan->nama_kontak)
                <div class="sub">a/n {{ $penjualan->pelanggan->nama_kontak }}</div>
            @endif
            <div class="sub">{{ $penjualan->pelanggan->telepon }}</div>
            <div class="sub" style="margin-top: 4px;">{{ $penjualan->pelanggan->alamat }}</div>
        </div>
    </div>

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 28px;">No</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 60px;">Satuan</th>
                <th class="text-center" style="width: 50px;">Qty</th>
                <th class="text-right" style="width: 120px;">Harga Satuan</th>
                <th class="text-right" style="width: 120px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan->details as $i => $detail)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $detail->produk->nama }}</td>
                <td class="text-center">{{ $detail->satuan->nama_satuan }}</td>
                <td class="text-center">{{ $detail->qty }}</td>
                <td class="text-right">Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($penjualan->adaDiskon())
            <tr>
                <td colspan="5" class="text-right">Subtotal</td>
                <td class="text-right">Rp {{ number_format((float) $penjualan->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right">{{ $penjualan->labelDiskon() }}</td>
                <td class="text-right">− Rp {{ number_format((float) $penjualan->diskon_nominal, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="5" class="text-right">Total</td>
                <td class="text-right">Rp {{ number_format((float) $penjualan->total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Pembayaran --}}
    <table class="pay-info">
        <tr>
            <td class="pay-left">
                @if($penjualan->catatan)
                <div class="note" style="margin-top:0; border-top:none; padding-top:0;">
                    <strong>Catatan:</strong> {{ $penjualan->catatan }}
                </div>
                @endif
            </td>
            <td class="pay-right text-right">
                <span class="badge-lunas">Lunas</span>
                <div style="font-size: 9pt; color:#555; margin-top: 6px;">{{ $penjualan->metode_bayar->label() }}</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Terima kasih atas kepercayaan Anda. · Invoice ini sah tanpa tanda tangan.
    </div>

</div>
</body>
</html>
