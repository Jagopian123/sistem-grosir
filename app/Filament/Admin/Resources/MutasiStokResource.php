<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\StockMovementType;
use App\Filament\Admin\Resources\MutasiStokResource\Pages;
use App\Models\MutasiStok;
use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MutasiStokResource extends Resource
{
    protected static ?string $model = MutasiStok::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Mutasi Stok';

    protected static ?string $modelLabel = 'Mutasi Stok';

    protected static ?string $pluralModelLabel = 'Mutasi Stok';

    protected static string|\UnitEnum|null $navigationGroup = 'Stok';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('produk.nama')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementType $state): string => $state->label())
                    ->color(fn (StockMovementType $state): string => $state->color()),

                TextColumn::make('qty')
                    ->label('Qty (satuan dasar)')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn (int $state, MutasiStok $record): string => (
                        $record->tipe->isInbound() ? "+{$state}" : "-{$state}"
                    ))
                    ->color(fn (MutasiStok $record): string => $record->tipe->isInbound() ? 'success' : 'danger'),

                TextColumn::make('stok_sebelum')
                    ->label('Stok Sebelum')
                    ->alignRight()
                    ->visibleFrom('lg'),

                TextColumn::make('stok_sesudah')
                    ->label('Stok Sesudah')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('referensi')
                    ->label('Referensi')
                    ->visibleFrom('md'),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('produk_id')
                    ->label('Produk')
                    ->options(Produk::orderBy('nama')->pluck('nama', 'id'))
                    ->searchable(),

                SelectFilter::make('tipe')
                    ->label('Tipe Mutasi')
                    ->options(collect(StockMovementType::cases())->mapWithKeys(
                        fn (StockMovementType $t) => [$t->value => $t->label()]
                    )),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'], fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '>=', $d))
                            ->when($data['sampai'], fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMutasiStoks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
