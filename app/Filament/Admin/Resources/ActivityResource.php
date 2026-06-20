<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    /** Peristiwa Eloquent → label Bahasa Indonesia. */
    private const EVENT_LABELS = [
        'created' => 'Dibuat',
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('causer');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Pengguna')
                    ->placeholder('Sistem')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Data')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::EVENT_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('attribute_changes')
                    ->label('Perubahan')
                    ->formatStateUsing(fn (mixed $state): string => self::formatChanges($state))
                    ->wrap()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options(self::EVENT_LABELS),

                SelectFilter::make('subject_type')
                    ->label('Data')
                    ->options(fn (): array => Activity::query()
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->filter()
                        ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])
                        ->all()),
            ])
            ->recordActions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** Ringkas perubahan atribut menjadi teks "kolom: lama → baru". */
    private static function formatChanges(mixed $state): string
    {
        $properties = $state instanceof Collection ? $state->toArray() : (array) $state;

        /** @var array<string, mixed> $baru */
        $baru = $properties['attributes'] ?? [];
        /** @var array<string, mixed> $lama */
        $lama = $properties['old'] ?? [];

        if ($baru === []) {
            return '—';
        }

        return collect($baru)
            ->map(function (mixed $nilaiBaru, string $kolom) use ($lama): string {
                $nilaiBaru = self::stringifyValue($nilaiBaru);

                if (array_key_exists($kolom, $lama)) {
                    return "{$kolom}: ".self::stringifyValue($lama[$kolom])." → {$nilaiBaru}";
                }

                return "{$kolom}: {$nilaiBaru}";
            })
            ->implode(Str::of("\n")->toString());
    }

    private static function stringifyValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'ya' : 'tidak';
        }

        if ($value === null) {
            return '∅';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '[…]';
        }

        return (string) $value;
    }
}
