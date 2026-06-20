<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PembelianResource\Pages;
use App\Models\Pembelian;
use App\Models\Produk;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Stok Masuk';

    protected static ?string $modelLabel = 'Pembelian';

    protected static ?string $pluralModelLabel = 'Stok Masuk';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembelian')
                ->schema([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'nama')
                        ->searchable()
                        ->preload()
                        ->required(),

                    DateTimePicker::make('tanggal')
                        ->label('Tanggal')
                        ->default(now())
                        ->required(),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('Detail Produk')
                ->description('Masukkan produk yang dibeli. Qty dalam satuan dasar (pcs, karung, botol, dll).')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->schema([
                            Select::make('produk_id')
                                ->label('Produk')
                                ->options(
                                    Produk::active()
                                        ->orderBy('nama')
                                        ->get()
                                        ->mapWithKeys(fn (Produk $p) => [
                                            $p->id => "{$p->nama} ({$p->satuan_dasar})",
                                        ])
                                )
                                ->searchable()
                                ->required()
                                ->columnSpan(2),

                            TextInput::make('qty')
                                ->label('Qty (satuan dasar)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->step(1),

                            TextInput::make('harga_beli')
                                ->label('Harga Beli (per satuan dasar)')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->minValue(0),
                        ])
                        ->columns(['default' => 1, 'md' => 4])
                        ->minItems(1)
                        ->addActionLabel('Tambah Produk')
                        ->reorderable(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_pembelian')
                    ->label('No. Pembelian')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('supplier.nama')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('details_count')
                    ->label('Jml Item')
                    ->counts('details')
                    ->alignRight()
                    ->visibleFrom('md'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('semibold'),
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
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'view' => Pages\ViewPembelian::route('/{record}'),
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
