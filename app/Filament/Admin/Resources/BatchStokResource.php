<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\ExpiryStatus;
use App\Filament\Admin\Resources\BatchStokResource\Pages;
use App\Models\BatchStok;
use App\Models\Produk;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BatchStokResource extends Resource
{
    protected static ?string $model = BatchStok::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Batch & Kadaluarsa';

    protected static ?string $modelLabel = 'Batch Stok';

    protected static ?string $pluralModelLabel = 'Batch & Kadaluarsa';

    protected static string|\UnitEnum|null $navigationGroup = 'Stok';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        // Hanya ED & kode batch yang boleh dikoreksi (mis. salah ketik). Qty tidak
        // diedit di sini agar tetap konsisten dengan buku besar mutasi_stok.
        return $schema->components([
            Section::make('Koreksi Batch')
                ->description('Hanya tanggal kadaluarsa dan kode batch yang bisa dikoreksi. Jumlah stok dikelola lewat transaksi.')
                ->schema([
                    DatePicker::make('tanggal_kadaluarsa')
                        ->label('Tanggal Kadaluarsa (ED)')
                        ->native(false),

                    TextInput::make('kode_batch')
                        ->label('Kode Batch')
                        ->maxLength(255),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('produk.nama')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('kode_batch')
                    ->label('Batch')
                    ->placeholder('—')
                    ->visibleFrom('md'),

                TextColumn::make('tanggal_kadaluarsa')
                    ->label('Kadaluarsa')
                    ->date('d/m/Y')
                    ->placeholder('Tanpa ED')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (BatchStok $record): ExpiryStatus => $record->status())
                    ->formatStateUsing(fn (ExpiryStatus $state): string => $state->label())
                    ->color(fn (ExpiryStatus $state): string => $state->color())
                    ->icon(fn (ExpiryStatus $state): string => $state->icon()),

                TextColumn::make('sisa_hari')
                    ->label('Sisa Hari')
                    ->state(fn (BatchStok $record): string => self::formatSisaHari($record))
                    ->alignRight()
                    ->visibleFrom('lg'),

                TextColumn::make('qty_sisa')
                    ->label('Sisa')
                    ->numeric()
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('qty_masuk')
                    ->label('Masuk')
                    ->numeric()
                    ->alignRight()
                    ->visibleFrom('lg'),

                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->visibleFrom('xl'),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('tanggal_kadaluarsa')
            ->filters([
                SelectFilter::make('produk_id')
                    ->label('Produk')
                    ->options(fn (): array => Produk::lacakKadaluarsa()->orderBy('nama')->pluck('nama', 'id')->all())
                    ->searchable(),

                Filter::make('perlu_perhatian')
                    ->label('Mendekati / Lewat ED')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('qty_sisa', '>', 0)
                        ->whereNotNull('tanggal_kadaluarsa')
                        ->whereDate('tanggal_kadaluarsa', '<=', now()->addDays(BatchStok::HARI_PERINGATAN)))
                    ->toggle(),

                Filter::make('kadaluarsa')
                    ->label('Sudah Kadaluarsa')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('qty_sisa', '>', 0)
                        ->whereNotNull('tanggal_kadaluarsa')
                        ->whereDate('tanggal_kadaluarsa', '<', now()))
                    ->toggle(),

                Filter::make('tersisa')
                    ->label('Masih Bersisa')
                    ->query(fn (Builder $query): Builder => $query->where('qty_sisa', '>', 0))
                    ->default()
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    private static function formatSisaHari(BatchStok $record): string
    {
        $sisa = $record->sisaHari();

        if ($sisa === null) {
            return '—';
        }

        if ($sisa < 0) {
            return 'Lewat '.abs($sisa).' hari';
        }

        return $sisa.' hari';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatchStoks::route('/'),
            'edit' => Pages\EditBatchStok::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('produk');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = BatchStok::query()->perluPerhatian()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $adaKadaluarsa = BatchStok::query()
            ->where('qty_sisa', '>', 0)
            ->whereNotNull('tanggal_kadaluarsa')
            ->whereDate('tanggal_kadaluarsa', '<', now())
            ->exists();

        return $adaKadaluarsa ? 'danger' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Batch mendekati / lewat kadaluarsa';
    }
}
