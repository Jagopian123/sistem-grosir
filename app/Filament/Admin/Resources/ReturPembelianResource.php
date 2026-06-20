<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReturPembelianResource\Pages;
use App\Models\DetailPembelian;
use App\Models\Pembelian;
use App\Models\ReturPembelian;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReturPembelianResource extends Resource
{
    protected static ?string $model = ReturPembelian::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?string $navigationLabel = 'Retur Pembelian';

    protected static ?string $modelLabel = 'Retur Pembelian';

    protected static ?string $pluralModelLabel = 'Retur Pembelian';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Retur')
                ->schema([
                    Select::make('pembelian_id')
                        ->label('Pembelian (No. Pembelian)')
                        ->options(
                            Pembelian::query()
                                ->latest('tanggal')
                                ->limit(200)
                                ->pluck('no_pembelian', 'id')
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->disabledOn('view'),

                    DateTimePicker::make('tanggal')
                        ->label('Tanggal Retur')
                        ->default(now())
                        ->required()
                        ->disabledOn('view'),

                    Textarea::make('catatan')
                        ->label('Alasan / Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('Item yang Diretur')
                ->description('Pilih produk dari pembelian terkait dan masukkan qty (satuan dasar) yang dikembalikan ke supplier. Stok akan berkurang.')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->visibleOn(['create'])
                        ->schema([
                            Select::make('produk_id')
                                ->label('Produk (qty dibeli)')
                                ->options(function (Get $get): array {
                                    $pembelianId = $get('../../pembelian_id');

                                    if (! $pembelianId) {
                                        return [];
                                    }

                                    return DetailPembelian::query()
                                        ->where('pembelian_id', $pembelianId)
                                        ->with('produk')
                                        ->get()
                                        ->mapWithKeys(fn (DetailPembelian $d): array => [
                                            $d->produk_id => "{$d->produk->nama} ({$d->produk->satuan_dasar}) — dibeli {$d->qty}",
                                        ])
                                        ->all();
                                })
                                ->searchable()
                                ->required()
                                ->columnSpan(3),

                            TextInput::make('qty')
                                ->label('Qty Retur (satuan dasar)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->step(1)
                                ->default(1),
                        ])
                        ->columns(['default' => 1, 'md' => 4])
                        ->minItems(1)
                        ->addActionLabel('Tambah Item')
                        ->reorderable(false),

                    // Tampilan read-only daftar item saat melihat retur tersimpan.
                    Repeater::make('details')
                        ->label('')
                        ->relationship('details')
                        ->visibleOn(['view'])
                        ->schema([
                            Select::make('produk_id')->label('Produk')->relationship('produk', 'nama')->disabled(),
                            TextInput::make('qty')->label('Qty')->disabled(),
                            TextInput::make('subtotal')->label('Subtotal')->prefix('Rp')->disabled(),
                        ])
                        ->columns(3)
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_retur')
                    ->label('No. Retur')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('pembelian.no_pembelian')
                    ->label('No. Pembelian')
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
                    ->label('Nilai Retur')
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
            'index' => Pages\ListReturPembelians::route('/'),
            'create' => Pages\CreateReturPembelian::route('/create'),
            'view' => Pages\ViewReturPembelian::route('/{record}'),
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
