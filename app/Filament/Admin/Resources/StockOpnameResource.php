<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockOpnameResource\Pages;
use App\Models\Produk;
use App\Models\StockOpname;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stock Opname';

    protected static ?string $modelLabel = 'Stock Opname';

    protected static ?string $pluralModelLabel = 'Stock Opname';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Opname')
                ->schema([
                    DateTimePicker::make('tanggal')
                        ->label('Tanggal Opname')
                        ->default(now())
                        ->required()
                        ->disabledOn('view'),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('Hasil Hitung Fisik')
                ->description('Pilih produk lalu masukkan jumlah hasil hitung fisik (satuan dasar). Selisih terhadap stok sistem akan dicatat sebagai penyesuaian.')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->visibleOn(['create'])
                        ->schema([
                            Select::make('produk_id')
                                ->label('Produk (stok sistem)')
                                ->options(
                                    Produk::query()
                                        ->active()
                                        ->orderBy('nama')
                                        ->get()
                                        ->mapWithKeys(fn (Produk $p): array => [
                                            $p->id => "{$p->nama} ({$p->satuan_dasar}) — sistem {$p->stok}",
                                        ])
                                        ->all()
                                )
                                ->searchable()
                                ->required()
                                ->distinct()
                                ->columnSpan(3),

                            TextInput::make('stok_fisik')
                                ->label('Stok Fisik (satuan dasar)')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(1)
                                ->default(0),
                        ])
                        ->columns(['default' => 1, 'md' => 4])
                        ->minItems(1)
                        ->addActionLabel('Tambah Produk')
                        ->reorderable(false),

                    // Tampilan read-only daftar item saat melihat opname tersimpan.
                    Repeater::make('details')
                        ->label('')
                        ->relationship('details')
                        ->visibleOn(['view'])
                        ->schema([
                            Select::make('produk_id')->label('Produk')->relationship('produk', 'nama')->disabled(),
                            TextInput::make('stok_sistem')->label('Stok Sistem')->disabled(),
                            TextInput::make('stok_fisik')->label('Stok Fisik')->disabled(),
                            TextInput::make('selisih')->label('Selisih')->disabled(),
                        ])
                        ->columns(4)
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_opname')
                    ->label('No. Opname')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('details_count')
                    ->label('Jml Produk')
                    ->counts('details')
                    ->alignRight()
                    ->visibleFrom('md'),

                TextColumn::make('total_selisih')
                    ->label('Selisih Bersih')
                    ->alignRight()
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $q): Builder => $q->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'view' => Pages\ViewStockOpname::route('/{record}'),
        ];
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
