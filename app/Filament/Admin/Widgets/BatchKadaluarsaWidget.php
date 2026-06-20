<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Enums\ExpiryStatus;
use App\Models\BatchStok;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BatchKadaluarsaWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BatchStok::query()
                    ->perluPerhatian()
                    ->with('produk')
                    ->orderBy('tanggal_kadaluarsa')
            )
            ->columns([
                Tables\Columns\TextColumn::make('produk.nama')
                    ->label('Produk')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('kode_batch')
                    ->label('Batch')
                    ->placeholder('—')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('tanggal_kadaluarsa')
                    ->label('Kadaluarsa')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (BatchStok $record): ExpiryStatus => $record->status())
                    ->formatStateUsing(fn (ExpiryStatus $state): string => $state->label())
                    ->color(fn (ExpiryStatus $state): string => $state->color())
                    ->icon(fn (ExpiryStatus $state): string => $state->icon()),

                Tables\Columns\TextColumn::make('qty_sisa')
                    ->label('Sisa Stok')
                    ->numeric()
                    ->alignRight(),
            ])
            ->heading('Batch Mendekati / Lewat Kadaluarsa')
            ->emptyStateHeading('Tidak ada batch yang perlu perhatian')
            ->emptyStateDescription('Semua stok berbatch masih jauh dari tanggal kadaluarsa.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }
}
