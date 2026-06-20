<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProdukResource\RelationManagers;

use App\Models\Produk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SatuanProdukRelationManager extends RelationManager
{
    protected static string $relationship = 'satuanProduk';

    protected static ?string $title = 'Satuan Produk';

    protected static ?string $modelLabel = 'Satuan';

    protected static ?string $pluralModelLabel = 'Satuan';

    public function form(Schema $schema): Schema
    {
        /** @var Produk $produk */
        $produk = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('nama_satuan')
                ->label('Nama Satuan')
                ->placeholder('pcs, dus, karton...')
                ->required()
                ->maxLength(50),

            TextInput::make('konversi')
                ->label("Konversi ke {$produk->satuan_dasar}")
                ->helperText("Misal: 1 dus berisi berapa {$produk->satuan_dasar}?")
                ->numeric()
                ->required()
                ->minValue(1)
                ->step(1)
                ->default(1),

            TextInput::make('harga_jual')
                ->label('Harga Jual per Satuan')
                ->helperText('Harga default bila qty di bawah tingkat manapun.')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->minValue(0),

            Section::make('Harga Bertingkat (per Kuantitas)')
                ->description('Opsional. Harga grosir berdasarkan jumlah beli. Tingkat dengan qty minimum terpenuhi paling tinggi yang dipakai.')
                ->schema([
                    Repeater::make('hargaTingkat')
                        ->relationship()
                        ->label('')
                        ->schema([
                            TextInput::make('min_qty')
                                ->label('Qty Minimum')
                                ->helperText('Berlaku bila qty beli >= nilai ini (dalam satuan di atas).')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->step(1),

                            TextInput::make('harga')
                                ->label('Harga per Satuan')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->minValue(0),
                        ])
                        ->columns(['default' => 1, 'md' => 2])
                        ->addActionLabel('Tambah Tingkat Harga')
                        ->defaultItems(0)
                        ->reorderable(false),
                ])
                ->columnSpanFull()
                ->collapsible(),
        ])->columns(['default' => 1, 'md' => 3]);
    }

    public function table(Table $table): Table
    {
        /** @var Produk $produk */
        $produk = $this->getOwnerRecord();

        return $table
            ->columns([
                TextColumn::make('nama_satuan')
                    ->label('Nama Satuan')
                    ->sortable(),

                TextColumn::make('konversi')
                    ->label("Konversi (ke {$produk->satuan_dasar})")
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('harga_tingkat_count')
                    ->label('Tingkat Harga')
                    ->counts('hargaTingkat')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state} tingkat" : '—')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Satuan'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
