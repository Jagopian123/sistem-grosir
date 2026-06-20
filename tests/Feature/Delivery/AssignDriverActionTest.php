<?php

declare(strict_types=1);

use App\Actions\Delivery\AssignDriverAction;
use App\Actions\Delivery\MarkDeliveredAction;
use App\Enums\DeliveryStatus;
use App\Models\Penjualan;
use App\Models\Sopir;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('assign sopir mengubah status ke dikirim dan menyimpan sopir_id', function () {
    $sopir = Sopir::factory()->create();
    $penjualan = Penjualan::factory()->create([
        'status_kirim' => DeliveryStatus::SiapKirim,
        'sopir_id' => null,
    ]);

    app(AssignDriverAction::class)->execute($penjualan, $sopir);

    $fresh = $penjualan->fresh();
    expect($fresh->sopir_id)->toBe($sopir->id)
        ->and($fresh->status_kirim)->toBe(DeliveryStatus::Dikirim);
});

test('assign sopir gagal jika status bukan siap_kirim', function () {
    $sopir = Sopir::factory()->create();
    $penjualan = Penjualan::factory()->dikirim()->create();

    expect(fn () => app(AssignDriverAction::class)->execute($penjualan, $sopir))
        ->toThrow(RuntimeException::class, 'Siap Kirim');
});

test('mark delivered mengubah status ke terkirim', function () {
    $penjualan = Penjualan::factory()->dikirim()->create();

    app(MarkDeliveredAction::class)->execute($penjualan);

    expect($penjualan->fresh()->status_kirim)->toBe(DeliveryStatus::Terkirim);
});

test('mark delivered gagal jika status bukan dikirim', function () {
    $penjualan = Penjualan::factory()->create([
        'status_kirim' => DeliveryStatus::SiapKirim,
    ]);

    expect(fn () => app(MarkDeliveredAction::class)->execute($penjualan))
        ->toThrow(RuntimeException::class, 'Dikirim');
});

test('mark delivered gagal jika status sudah terkirim', function () {
    $penjualan = Penjualan::factory()->terkirim()->create();

    expect(fn () => app(MarkDeliveredAction::class)->execute($penjualan))
        ->toThrow(RuntimeException::class, 'Dikirim');
});

test('alur lengkap siap_kirim ke dikirim ke terkirim berhasil', function () {
    $sopir = Sopir::factory()->create();
    $penjualan = Penjualan::factory()->create(['status_kirim' => DeliveryStatus::SiapKirim]);

    app(AssignDriverAction::class)->execute($penjualan, $sopir);
    expect($penjualan->fresh()->status_kirim)->toBe(DeliveryStatus::Dikirim);

    app(MarkDeliveredAction::class)->execute($penjualan->fresh());
    expect($penjualan->fresh()->status_kirim)->toBe(DeliveryStatus::Terkirim);
});
