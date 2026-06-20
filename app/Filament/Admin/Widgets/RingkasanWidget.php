<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingkasanWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = -5;

    protected function getStats(): array
    {
        $omzetHariIni = (float) Penjualan::whereDate('tanggal', today())->sum('total');
        $omzetBulanIni = (float) Penjualan::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('total');
        $transaksiHariIni = Penjualan::whereDate('tanggal', today())->count();
        $transaksiBulanIni = Penjualan::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->count();

        return [
            Stat::make('Omzet Hari Ini', 'Rp '.number_format($omzetHariIni, 0, ',', '.'))
                ->description('Total penjualan hari ini')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Omzet Bulan Ini', 'Rp '.number_format($omzetBulanIni, 0, ',', '.'))
                ->description(now()->translatedFormat('F Y'))
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Transaksi Hari Ini', (string) $transaksiHariIni)
                ->description('Jumlah invoice hari ini')
                ->icon('heroicon-o-shopping-cart')
                ->color('warning'),

            Stat::make('Transaksi Bulan Ini', (string) $transaksiBulanIni)
                ->description('Jumlah invoice bulan ini')
                ->icon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}
