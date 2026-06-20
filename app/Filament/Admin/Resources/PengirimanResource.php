<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Actions\Delivery\AssignDriverAction;
use App\Actions\Delivery\MarkDeliveredAction;
use App\Enums\DeliveryStatus;
use App\Filament\Admin\Resources\PengirimanResource\Pages;
use App\Models\Penjualan;
use App\Models\Sopir;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PengirimanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Antrian Kirim';

    protected static ?string $modelLabel = 'Pengiriman';

    protected static ?string $pluralModelLabel = 'Pengiriman';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengiriman';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'pengiriman';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['pelanggan', 'sopir'])
            ->whereIn('status_kirim', [
                DeliveryStatus::SiapKirim->value,
                DeliveryStatus::Dikirim->value,
            ]);
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

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('pelanggan.nama_toko')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pelanggan.alamat')
                    ->label('Alamat')
                    ->limit(40)
                    ->visibleFrom('lg'),

                TextColumn::make('sopir.nama')
                    ->label('Sopir')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('status_kirim')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (DeliveryStatus $state): string => $state->label())
                    ->color(fn (DeliveryStatus $state): string => $state->color()),
            ])
            ->searchDebounce('500ms')
            ->defaultSort('tanggal', 'asc')
            ->filters([
                SelectFilter::make('status_kirim')
                    ->label('Status Kirim')
                    ->options(collect(DeliveryStatus::cases())->mapWithKeys(
                        fn (DeliveryStatus $s) => [$s->value => $s->label()]
                    )),

                Filter::make('tanpa_sopir')
                    ->label('Belum Ada Sopir')
                    ->query(fn (Builder $q): Builder => $q->whereNull('sopir_id'))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('assignSopir')
                    ->label('Assign Sopir')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (Penjualan $record): bool => $record->status_kirim === DeliveryStatus::SiapKirim)
                    ->form([
                        Select::make('sopir_id')
                            ->label('Pilih Sopir')
                            ->options(Sopir::query()->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, Penjualan $record): void {
                        $sopir = Sopir::findOrFail($data['sopir_id']);

                        try {
                            app(AssignDriverAction::class)->execute($record, $sopir);

                            Notification::make()
                                ->title('Sopir berhasil di-assign')
                                ->body("{$sopir->nama} ditetapkan untuk {$record->no_invoice}. Status berubah ke Dikirim.")
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Gagal assign sopir')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Assign Sopir')
                    ->modalSubmitActionLabel('Assign'),

                Action::make('tandaiTerkirim')
                    ->label('Tandai Terkirim')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Penjualan $record): bool => $record->status_kirim === DeliveryStatus::Dikirim)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Terkirim')
                    ->modalDescription(fn (Penjualan $record): string => "Tandai {$record->no_invoice} ke {$record->pelanggan->nama_toko} sebagai sudah terkirim?")
                    ->modalSubmitActionLabel('Ya, Sudah Terkirim')
                    ->action(function (Penjualan $record): void {
                        try {
                            app(MarkDeliveredAction::class)->execute($record);

                            Notification::make()
                                ->title('Status diperbarui')
                                ->body("{$record->no_invoice} ditandai sebagai Terkirim.")
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Gagal memperbarui status')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('cetakSuratJalan')
                    ->label('Cetak Surat Jalan')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Penjualan $record): string => route('surat-jalan', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListPengiriman::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
