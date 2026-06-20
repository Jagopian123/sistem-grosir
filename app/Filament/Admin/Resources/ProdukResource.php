<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProdukResource\Pages;
use App\Filament\Admin\Resources\ProdukResource\RelationManagers\SatuanProdukRelationManager;
use App\Models\Produk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Produk')
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama Produk')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->relationship('kategori', 'nama')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('nama')
                                ->label('Nama Kategori')
                                ->required()
                                ->maxLength(255),
                        ]),

                    TextInput::make('satuan_dasar')
                        ->label('Satuan Dasar')
                        ->placeholder('pcs, kg, liter...')
                        ->required()
                        ->maxLength(50),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('Stok & Harga')
                ->schema([
                    TextInput::make('harga_beli')
                        ->label('Harga Beli (HPP)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(0),

                    TextInput::make('stok')
                        ->label('Stok Awal')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(1),

                    TextInput::make('stok_min')
                        ->label('Stok Minimum (Alert)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(1),

                    Toggle::make('aktif')
                        ->label('Produk Aktif')
                        ->default(true),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('satuan_dasar')
                    ->label('Satuan')
                    ->visibleFrom('md'),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->alignRight()
                    ->sortable()
                    ->color(fn (Produk $record): string => $record->stok <= $record->stok_min ? 'danger' : 'success'),

                TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable()
                    ->visibleFrom('md'),

                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->visibleFrom('lg'),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('nama')
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Hanya Nonaktif'),

                Filter::make('stok_menipis')
                    ->label('Stok Menipis')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stok', '<=', 'stok_min'))
                    ->toggle(),
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

    public static function getRelations(): array
    {
        return [
            SatuanProdukRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Produk::lowStock()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Produk::lowStock()->exists() ? 'danger' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Produk stok menipis';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'kategori.nama'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Produk $record */
        return $record->nama;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Produk $record */
        return [
            'Kategori' => $record->kategori->nama,
        ];
    }
}
