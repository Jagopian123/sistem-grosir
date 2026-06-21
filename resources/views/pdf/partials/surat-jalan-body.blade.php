{{-- Isi satu Surat Jalan. Dipakai oleh cetak satuan & cetak massal.
     Bungkus pemanggil yang menyediakan $penjualan (Penjualan ter-eager-load). --}}

{{-- Header --}}
<div class="header">
    <table class="header-inner">
        <tr>
            <td>
                <div class="toko-nama">Toko Grosir</div>
                <div class="toko-sub">Sistem Manajemen Grosir Internal</div>
            </td>
            <td class="doc-title">
                <h2>Surat Jalan</h2>
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
        <td class="meta-label">Status</td>
        <td class="meta-sep">:</td>
        <td><strong>{{ $penjualan->status_kirim->label() }}</strong></td>
    </tr>
    <tr>
        <td class="meta-label">Metode Bayar</td>
        <td class="meta-sep">:</td>
        <td>{{ $penjualan->metode_bayar->label() }}</td>
        <td class="meta-label">Dicetak</td>
        <td class="meta-sep">:</td>
        <td>{{ now()->translatedFormat('d F Y H:i') }}</td>
    </tr>
</table>

{{-- Pelanggan + Sopir --}}
<table class="two-col">
    <tr>
        <td class="col-left">
            <div class="box">
                <div class="box-title">Pelanggan / Tujuan Pengiriman</div>
                <div class="box-content">
                    <div class="nama">{{ $penjualan->pelanggan->nama_toko }}</div>
                    @if($penjualan->pelanggan->nama_kontak)
                        <div class="sub">a/n {{ $penjualan->pelanggan->nama_kontak }}</div>
                    @endif
                    <div class="sub">{{ $penjualan->pelanggan->telepon }}</div>
                    <div class="sub" style="margin-top: 4px;">{{ $penjualan->pelanggan->alamat }}</div>
                </div>
            </div>
        </td>
        <td class="col-right">
            <div class="box">
                <div class="box-title">Informasi Pengiriman</div>
                <div class="box-content">
                    @if($penjualan->sopir)
                        <div class="nama">{{ $penjualan->sopir->nama }}</div>
                        <div class="sub">Sopir: {{ $penjualan->sopir->telepon }}</div>
                    @else
                        <div class="sub" style="color:#aaa; font-style: italic;">Sopir belum di-assign</div>
                    @endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- Items --}}
<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 28px;">No</th>
            <th>Nama Produk</th>
            <th class="text-center" style="width: 60px;">Satuan</th>
            <th class="text-center" style="width: 50px;">Qty</th>
            <th class="text-right" style="width: 110px;">Harga Satuan</th>
            <th class="text-right" style="width: 110px;">Subtotal</th>
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
        <tr class="total-row">
            <td colspan="5" class="text-right">Total</td>
            <td class="text-right">Rp {{ number_format((float) $penjualan->total, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

@if($penjualan->catatan)
<div class="note">
    <strong>Catatan:</strong> {{ $penjualan->catatan }}
</div>
@endif

{{-- Tanda tangan --}}
<table class="sign-table">
    <tr>
        <td>
            <div class="sign-label">Dibuat oleh</div>
            <div class="sign-box">
                <div>( ______________ )</div>
                <div style="margin-top: 4px;">Admin</div>
            </div>
        </td>
        <td>
            <div class="sign-label">Sopir / Pengantar</div>
            <div class="sign-box">
                <div>( ______________ )</div>
                <div style="margin-top: 4px;">{{ $penjualan->sopir?->nama ?? '___________' }}</div>
            </div>
        </td>
        <td>
            <div class="sign-label">Penerima</div>
            <div class="sign-box">
                <div>( ______________ )</div>
                <div style="margin-top: 4px;">{{ $penjualan->pelanggan->nama_toko }}</div>
            </div>
        </td>
    </tr>
</table>
