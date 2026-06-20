<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProdukResource\RelationManagers;

use App\Models\Produk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
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
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->minValue(0),
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
