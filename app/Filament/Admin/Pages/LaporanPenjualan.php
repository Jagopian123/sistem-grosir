<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\PaymentMethod;
use App\Models\Penjualan;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanPenjualan extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $title = 'Laporan Penjualan';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.laporan-penjualan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Penjualan::query()
                    ->with(['pelanggan'])
                    ->latest('tanggal')
            )
            ->columns([
                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('pelanggan.nama_toko')
                    ->label('Pelanggan')
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('metode_bayar')
                    ->label('Bayar')
                    ->badge()
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label())
                    ->color(fn (PaymentMethod $state): string => $state->color())
                    ->visibleFrom('lg'),
            ])
            ->searchDebounce('500ms')
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
                                fn (Builder $q, string $v): Builder => $q->whereDate('tanggal', '>=', $v)
                            )
                            ->when(
                                $data['sampai'] ?? null,
                                fn (Builder $q, string $v): Builder => $q->whereDate('tanggal', '<=', $v)
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

                SelectFilter::make('metode_bayar')
                    ->label('Metode Bayar')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m): array => [$m->value => $m->label()]
                    )),
            ])
            ->heading('Laporan Penjualan');
    }
}
