<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Produk;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StokMenipisWidget extends BaseWidget
{
    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Produk::query()
                    ->lowStock()
                    ->active()
                    ->with('kategori')
                    ->select(['id', 'kategori_id', 'nama', 'satuan_dasar', 'stok', 'stok_min'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Produk')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('kategori.nama')
                    ->label('Kategori'),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('stok_min')
                    ->label('Stok Min')
                    ->numeric(),

                Tables\Columns\TextColumn::make('satuan_dasar')
                    ->label('Satuan'),
            ])
            ->heading('Produk Stok Menipis')
            ->emptyStateHeading('Semua stok aman')
            ->emptyStateDescription('Tidak ada produk yang stoknya di bawah minimum.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }
}
