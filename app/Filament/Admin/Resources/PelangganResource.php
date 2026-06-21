<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    protected static string|\UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nama_toko';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('nama_toko')
                        ->label('Nama Toko')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('nama_kontak')
                        ->label('Nama Kontak')
                        ->maxLength(255),

                    TextInput::make('telepon')
                        ->label('Telepon')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    Textarea::make('alamat')
                        ->label('Alamat')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_toko')
                    ->label('Nama Toko')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereFullTextSearch($search))
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('nama_kontak')
                    ->label('Kontak')
                    ->searchable()
                    ->visibleFrom('md'),

                TextColumn::make('telepon')
                    ->label('Telepon')
                    ->searchable(),

                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50)
                    ->visibleFrom('lg')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('nama_toko')
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelanggans::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit' => Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_toko', 'nama_kontak', 'telepon'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Pelanggan $record */
        return $record->nama_toko;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Pelanggan $record */
        return [
            'Kontak' => $record->nama_kontak ?? '-',
            'Telp' => $record->telepon,
        ];
    }
}
