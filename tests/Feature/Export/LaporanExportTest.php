<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\FormatExport;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Admin\Pages\LaporanPenjualan;
use App\Jobs\GenerateLaporanExportJob;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\User;
use App\Support\Laporan\Definisi\LaporanLabaKotorExport;
use App\Support\Laporan\Definisi\LaporanPenjualanExport;
use App\Support\Laporan\KolomLaporan;
use App\Support\Laporan\PenulisExcelLaporan;
use App\Support\Laporan\PenulisPdfLaporan;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->owner = User::factory()->create();
    $this->owner->assignRole(Role::Owner->value);
    actingAs($this->owner);

    $produk = Produk::factory()->create(['stok' => 100, 'harga_beli' => 4_000]);
    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'konversi' => 24,
        'harga_jual' => 120_000,
    ]);

    $this->buatPenjualan = fn (): Penjualan => app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: [['satuan_id' => $satuan->id, 'qty' => 2]],
        metode: PaymentMethod::Tunai,
    );
});

test('job export Excel menulis berkas xlsx ke storage dan memberi notifikasi unduh', function () {
    Storage::fake('local');

    $p = ($this->buatPenjualan)();

    (new GenerateLaporanExportJob(LaporanPenjualanExport::class, FormatExport::Excel, [$p->id], $this->owner->id))
        ->handle(app(PenulisExcelLaporan::class), app(PenulisPdfLaporan::class));

    $files = Storage::disk('local')->files('laporan');
    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.xlsx')
        ->and(Storage::disk('local')->size($files[0]))->toBeGreaterThan(0);

    $this->owner->refresh();
    $data = $this->owner->notifications->first()->data;
    expect($data['title'])->toContain('siap diunduh')
        ->and($data['actions'][0]['url'])->toContain('unduhan/laporan')
        ->and($data['actions'][0]['url'])->toContain('signature=');
});

test('job export PDF menulis berkas pdf yang valid ke storage', function () {
    Storage::fake('local');

    $p = ($this->buatPenjualan)();

    (new GenerateLaporanExportJob(LaporanPenjualanExport::class, FormatExport::Pdf, [$p->id], $this->owner->id))
        ->handle(app(PenulisExcelLaporan::class), app(PenulisPdfLaporan::class));

    $files = Storage::disk('local')->files('laporan');
    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.pdf')
        ->and(Storage::disk('local')->get($files[0]))->toStartWith('%PDF');
});

test('job dengan id kosong tidak menyimpan berkas tapi memberi notifikasi gagal', function () {
    Storage::fake('local');

    (new GenerateLaporanExportJob(LaporanPenjualanExport::class, FormatExport::Excel, [], $this->owner->id))
        ->handle(app(PenulisExcelLaporan::class), app(PenulisPdfLaporan::class));

    expect(Storage::disk('local')->files('laporan'))->toBeEmpty();

    $this->owner->refresh();
    expect($this->owner->notifications->first()->data['title'])->toContain('Gagal');
});

test('definisi laba kotor menghitung HPP & laba kotor dengan benar', function () {
    $p = ($this->buatPenjualan)();

    $definisi = new LaporanLabaKotorExport;
    $record = $definisi->baseQuery()->whereKey($p->id)->firstOrFail();

    $nilai = collect($definisi->kolom())->mapWithKeys(
        fn (KolomLaporan $k): array => [$k->label => ($k->nilai)($record)],
    );

    // omzet 2 × 120.000 = 240.000; HPP 2 × 24 × 4.000 = 192.000; laba = 48.000
    expect((float) $nilai['Omzet'])->toBe(240_000.0)
        ->and((float) $nilai['HPP'])->toBe(192_000.0)
        ->and((float) $nilai['Laba Kotor'])->toBe(48_000.0);
});

test('tombol export di halaman laporan mendispatch job alih-alih memproses sinkron', function () {
    Queue::fake();

    ($this->buatPenjualan)();

    Livewire::test(LaporanPenjualan::class)
        ->callAction('exportExcel');

    Queue::assertPushed(
        GenerateLaporanExportJob::class,
        fn (GenerateLaporanExportJob $job): bool => true,
    );
});

test('tombol export tanpa data tidak mendispatch job', function () {
    Queue::fake();

    Livewire::test(LaporanPenjualan::class)
        ->callAction('exportExcel');

    Queue::assertNothingPushed();
});

test('route unduhan laporan menolak akses tanpa tanda tangan URL', function () {
    get(route('unduhan.laporan', ['file' => 'apa-saja.xlsx']))
        ->assertForbidden();
});
