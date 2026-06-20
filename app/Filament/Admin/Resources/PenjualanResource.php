<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\DeliveryStatus;
use App\Enums\DiscountType;
use App\Enums\InvoiceFormat;
use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\PenjualanResource\Pages;
use App\Models\Penjualan;
use App\Models\SatuanProduk;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    protected static ?string $pluralModelLabel = 'Penjualan';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Penjualan')
                ->schema([
                    Select::make('pelanggan_id')
                        ->label('Pelanggan')
                        ->relationship('pelanggan', 'nama_toko')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('metode_bayar')
                        ->label('Metode Bayar')
                        ->options(collect(PaymentMethod::cases())->mapWithKeys(
                            fn (PaymentMethod $m) => [$m->value => $m->label()]
                        ))
                        ->required()
                        ->default(PaymentMethod::Tunai->value),

                    DateTimePicker::make('tanggal')
                        ->label('Tanggal')
                        ->default(now())
                        ->required()
                        ->visibleOn('view'),

                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('Item Penjualan')
                ->description('Pilih produk & satuan, masukkan qty. Harga otomatis mengikuti tingkat harga grosir sesuai jumlah beli.')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->schema([
                            Select::make('satuan_id')
                                ->label('Produk & Satuan')
                                ->options(
                                    SatuanProduk::query()
                                        ->with('produk')
                                        ->get()
                                        ->filter(fn (SatuanProduk $s) => $s->produk?->aktif)
                                        ->mapWithKeys(fn (SatuanProduk $s) => [
                                            $s->id => "{$s->produk->nama} — {$s->nama_satuan} (Rp ".number_format((float) $s->harga_jual, 0, ',', '.').')',
                                        ])
                                )
                                ->searchable()
                                ->required()
                                ->live()
                                ->columnSpan(2),

                            TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->step(1)
                                ->default(1)
                                ->live(onBlur: true),

                            Placeholder::make('harga_preview')
                                ->label('Harga Berlaku')
                                ->content(fn (Get $get): string => self::previewHargaItem($get('satuan_id'), $get('qty')))
                                ->columnSpan(['default' => 1, 'md' => 2]),
                        ])
                        ->columns(['default' => 1, 'md' => 4])
                        ->minItems(1)
                        ->addActionLabel('Tambah Item')
                        ->reorderable(false)
                        ->live(),
                ]),

            Section::make('Diskon Nota (Opsional)')
                ->description('Potongan harga di tingkat nota. Kosongkan bila tidak ada diskon. Tidak memengaruhi stok.')
                ->schema([
                    Select::make('diskon_tipe')
                        ->label('Tipe Diskon')
                        ->options(collect(DiscountType::cases())->mapWithKeys(
                            fn (DiscountType $t) => [$t->value => $t->label()]
                        ))
                        ->placeholder('Tanpa diskon')
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            if (blank($state)) {
                                $set('diskon_nilai', 0);
                            }
                        }),

                    TextInput::make('diskon_nilai')
                        ->label(fn (Get $get): string => $get('diskon_tipe') === DiscountType::Persen->value ? 'Nilai Diskon (%)' : 'Nilai Diskon (Rp)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => filled($get('diskon_tipe')))
                        ->maxValue(fn (Get $get): ?int => $get('diskon_tipe') === DiscountType::Persen->value ? 100 : null),

                    Placeholder::make('ringkasan')
                        ->label('Ringkasan')
                        ->content(fn (Get $get) => self::previewRingkasan($get))
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2])
                ->visibleOn('create'),
        ]);
    }

    /**
     * Pratinjau subtotal, diskon, dan total nota di form create.
     */
    private static function previewRingkasan(Get $get): string
    {
        $rp = fn (float $n): string => 'Rp '.number_format($n, 0, ',', '.');

        $subtotal = 0.0;
        /** @var array<int, array{satuan_id?: mixed, qty?: mixed}> $items */
        $items = $get('items') ?? [];

        foreach ($items as $item) {
            $satuanId = $item['satuan_id'] ?? null;
            if (blank($satuanId)) {
                continue;
            }

            $satuan = SatuanProduk::with('hargaTingkat')->find($satuanId);
            if (! $satuan) {
                continue;
            }

            $qty = max(1, (int) ($item['qty'] ?? 1));
            $subtotal += $satuan->hargaUntukQty($qty) * $qty;
        }

        $tipe = $get('diskon_tipe');
        $nilai = (float) ($get('diskon_nilai') ?? 0);

        $diskon = 0.0;
        if ($tipe === DiscountType::Persen->value) {
            $diskon = $subtotal * min($nilai, 100.0) / 100;
        } elseif ($tipe === DiscountType::Nominal->value) {
            $diskon = $nilai;
        }
        $diskon = round(max(0.0, min($diskon, $subtotal)), 2);

        $total = $subtotal - $diskon;

        return "Subtotal: {$rp($subtotal)}  ·  Diskon: {$rp($diskon)}  ·  Total: {$rp($total)}";
    }

    /**
     * Pratinjau harga per satuan (mempertimbangkan harga bertingkat) + subtotal untuk item form.
     */
    private static function previewHargaItem(mixed $satuanId, mixed $qty): string
    {
        if (blank($satuanId)) {
            return 'Pilih produk & satuan dulu.';
        }

        $satuan = SatuanProduk::with('hargaTingkat')->find($satuanId);

        if (! $satuan) {
            return '—';
        }

        $qty = max(1, (int) $qty);
        $harga = $satuan->hargaUntukQty($qty);
        $subtotal = $harga * $qty;

        $rp = fn (float $n): string => 'Rp '.number_format($n, 0, ',', '.');

        return "{$rp($harga)} / satuan  ·  Subtotal {$rp($subtotal)}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('pelanggan.nama_toko')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('diskon_nominal')
                    ->label('Diskon')
                    ->money('IDR')
                    ->sortable()
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->visibleFrom('md'),

                TextColumn::make('status_kirim')
                    ->label('Status Kirim')
                    ->badge()
                    ->formatStateUsing(fn (DeliveryStatus $state): string => $state->label())
                    ->color(fn (DeliveryStatus $state): string => $state->color()),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('status_kirim')
                    ->label('Status Kirim')
                    ->options(collect(DeliveryStatus::cases())->mapWithKeys(
                        fn (DeliveryStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('metode_bayar')
                    ->label('Metode Bayar')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(
                        fn (PaymentMethod $m) => [$m->value => $m->label()]
                    )),

                Filter::make('hari_ini')
                    ->label('Hari Ini')
                    ->query(fn (Builder $q): Builder => $q->whereDate('tanggal', today()))
                    ->toggle(),

                Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $q): Builder => $q->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    Action::make('invoiceA4')
                        ->label(InvoiceFormat::A4->label())
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Penjualan $record): string => route('invoice', [$record, InvoiceFormat::A4->value]))
                        ->openUrlInNewTab(),
                    Action::make('struk58')
                        ->label(InvoiceFormat::Thermal58->label())
                        ->icon('heroicon-o-receipt-percent')
                        ->url(fn (Penjualan $record): string => route('invoice', [$record, InvoiceFormat::Thermal58->value]))
                        ->openUrlInNewTab(),
                    Action::make('struk80')
                        ->label(InvoiceFormat::Thermal80->label())
                        ->icon('heroicon-o-receipt-percent')
                        ->url(fn (Penjualan $record): string => route('invoice', [$record, InvoiceFormat::Thermal80->value]))
                        ->openUrlInNewTab(),
                ])
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray'),
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
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'view' => Pages\ViewPenjualan::route('/{record}'),
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
