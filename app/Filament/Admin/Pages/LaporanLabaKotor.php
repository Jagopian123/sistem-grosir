<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanLabaKotor extends Page implements HasActions, HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Laba Kotor';

    protected static ?string $title = 'Laporan Laba Kotor';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.admin.pages.laporan-laba-kotor';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Penjualan::query()
                    ->join('detail_penjualans', 'detail_penjualans.penjualan_id', '=', 'penjualans.id')
                    ->join('satuan_produks', 'satuan_produks.id', '=', 'detail_penjualans.satuan_id')
                    ->join('produks', 'produks.id', '=', 'detail_penjualans.produk_id')
                    ->join('pelanggans', 'pelanggans.id', '=', 'penjualans.pelanggan_id')
                    ->selectRaw('
                        penjualans.id,
                        penjualans.no_invoice,
                        penjualans.tanggal,
                        pelanggans.nama_toko as pelanggan_nama,
                        penjualans.total as omzet,
                        COALESCE(SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli), 0) as hpp,
                        penjualans.total - COALESCE(SUM(detail_penjualans.qty * satuan_produks.konversi * produks.harga_beli), 0) as laba_kotor
                    ')
                    ->groupBy(
                        'penjualans.id',
                        'penjualans.no_invoice',
                        'penjualans.tanggal',
                        'pelanggans.nama_toko',
                        'penjualans.total'
                    )
                    ->orderByDesc('penjualans.tanggal')
            )
            ->columns([
                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->weight('medium'),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('pelanggan_nama')
                    ->label('Pelanggan')
                    ->visibleFrom('md'),

                TextColumn::make('omzet')
                    ->label('Omzet')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('hpp')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('laba_kotor')
                    ->label('Laba Kotor')
                    ->money('IDR')
                    ->sortable()
                    ->weight('semibold')
                    ->color(fn (mixed $state): string => (float) $state >= 0 ? 'success' : 'danger'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Filter::make('rentang_tanggal')
                    ->schema([
                        DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'] ?? null,
                                fn (Builder $q, string $v): Builder => $q->whereDate('penjualans.tanggal', '>=', $v)
                            )
                            ->when(
                                $data['sampai'] ?? null,
                                fn (Builder $q, string $v): Builder => $q->whereDate('penjualans.tanggal', '<=', $v)
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $dari = $data['dari'] ?? null;
                        $sampai = $data['sampai'] ?? null;

                        if ($dari && $sampai) {
                            return "Tanggal: {$dari} – {$sampai}";
                        }
                        if ($dari) {
                            return "Dari: {$dari}";
                        }
                        if ($sampai) {
                            return "Sampai: {$sampai}";
                        }

                        return null;
                    }),
            ])
            ->heading('Laporan Laba Kotor');
    }
}
