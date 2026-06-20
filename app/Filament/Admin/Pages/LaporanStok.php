<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\Produk;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
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

class LaporanStok extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Laporan Stok';

    protected static ?string $title = 'Laporan Stok Saat Ini';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.admin.pages.laporan-stok';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Produk::query()
                    ->with('kategori')
                    ->select(['id', 'kategori_id', 'nama', 'satuan_dasar', 'stok', 'stok_min', 'harga_beli', 'aktif'])
                    ->orderBy('nama')
            )
            ->columns([
                TextColumn::make('nama')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Produk $record): string => $record->stok <= $record->stok_min ? 'danger' : 'success'),

                TextColumn::make('stok_min')
                    ->label('Stok Min')
                    ->numeric(),

                TextColumn::make('satuan_dasar')
                    ->label('Satuan'),

                TextColumn::make('harga_beli')
                    ->label('HPP/Satuan Dasar')
                    ->money('IDR')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('nilai_stok')
                    ->label('Nilai Stok')
                    ->state(fn (Produk $record): float => (float) $record->harga_beli * $record->stok)
                    ->money('IDR')
                    ->visibleFrom('lg'),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('nama')
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama'),

                Filter::make('stok_menipis')
                    ->label('Stok Menipis')
                    ->query(fn (Builder $q): Builder => $q->whereColumn('stok', '<=', 'stok_min'))
                    ->toggle(),

                Filter::make('hanya_aktif')
                    ->label('Hanya Produk Aktif')
                    ->query(fn (Builder $q): Builder => $q->where('aktif', true))
                    ->toggle()
                    ->default(true),
            ])
            ->heading('Laporan Stok Saat Ini');
    }
}
