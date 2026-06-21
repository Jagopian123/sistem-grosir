<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $judul }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #1a1a1a; }

        .page { padding: 20px 28px; }

        .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 15pt; font-weight: bold; }
        .header .sub { font-size: 8.5pt; color: #555; margin-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f0f0f0; font-size: 8.5pt; text-align: left; padding: 5px 7px; border: 1px solid #ccc; }
        table.data td { font-size: 8.5pt; padding: 4px 7px; border: 1px solid #ccc; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #fafafa; }

        .empty { text-align: center; color: #777; padding: 18px; font-style: italic; }
        .footer { margin-top: 10px; font-size: 8pt; color: #777; text-align: right; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1>{{ $judul }}</h1>
        <div class="sub">Dicetak: {{ $dicetak->format('d/m/Y H:i') }} &middot; {{ $baris->count() }} baris</div>
    </div>

    @if($baris->isEmpty())
        <div class="empty">Tidak ada data untuk ditampilkan.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach($header as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($baris as $row)
                    <tr>
                        @foreach($row as $sel)
                            <td>{{ $sel }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Sistem Grosir</div>
</div>
</body>
</html>
