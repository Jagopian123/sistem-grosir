<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Concerns\DapatMengeksporLaporan;
use App\Support\Laporan\Definisi\LaporanProdukTerlarisExport;
use App\Support\Laporan\DefinisiLaporan;
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

class LaporanProdukTerlaris extends Page implements HasActions, HasSchemas, HasTable
{
    use DapatMengeksporLaporan;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Produk Terlaris';

    protected static ?string $title = 'Laporan Produk Terlaris';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.admin.pages.laporan-produk-terlaris';

    protected function definisiExport(): DefinisiLaporan
    {
        return new LaporanProdukTerlarisExport;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->definisiExport()->baseQuery())
            ->columns([
                TextColumn::make('produk_nama')
                    ->label('Produk')
                    ->weight('medium'),

                TextColumn::make('total_qty')
                    ->label('Total Qty Terjual')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_omzet')
                    ->label('Total Omzet')
                    ->money('IDR')
                    ->sortable(),
            ])
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
            ->heading('Produk Terlaris')
            ->defaultPaginationPageOption(25);
    }
}
