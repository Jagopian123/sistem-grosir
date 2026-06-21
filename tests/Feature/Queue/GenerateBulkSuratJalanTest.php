<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Filament\Admin\Resources\PengirimanResource\Pages\ListPengiriman;
use App\Jobs\GenerateBulkSuratJalanJob;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\User;
use App\Support\SuratJalanPdf;
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

    $produk = Produk::factory()->create(['stok' => 100]);
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

test('job merender PDF gabungan, menyimpan ke storage, dan memberi notifikasi unduh', function () {
    Storage::fake('local');

    $p1 = ($this->buatPenjualan)();
    $p2 = ($this->buatPenjualan)();

    (new GenerateBulkSuratJalanJob([$p1->id, $p2->id], $this->owner->id))
        ->handle(app(SuratJalanPdf::class));

    $files = Storage::disk('local')->files('surat-jalan');
    expect($files)->toHaveCount(1)
        ->and($files[0])->toEndWith('.pdf');

    $this->owner->refresh();
    expect($this->owner->notifications)->toHaveCount(1);

    $data = $this->owner->notifications->first()->data;
    expect($data['title'])->toContain('siap diunduh')
        ->and($data['actions'][0]['url'])->toContain('unduhan/surat-jalan')
        ->and($data['actions'][0]['url'])->toContain('signature=');
});

test('job dengan id kosong tidak menyimpan file tapi tetap memberi notifikasi gagal', function () {
    Storage::fake('local');

    (new GenerateBulkSuratJalanJob([999999], $this->owner->id))
        ->handle(app(SuratJalanPdf::class));

    expect(Storage::disk('local')->files('surat-jalan'))->toBeEmpty();

    $this->owner->refresh();
    expect($this->owner->notifications)->toHaveCount(1)
        ->and($this->owner->notifications->first()->data['title'])->toContain('Gagal');
});

test('bulk action di antrian kirim mendispatch job alih-alih merender sinkron', function () {
    Queue::fake();

    $p1 = ($this->buatPenjualan)();
    $p2 = ($this->buatPenjualan)();

    Livewire::test(ListPengiriman::class)
        ->callTableBulkAction('cetakSuratJalanMassal', [$p1, $p2]);

    Queue::assertPushed(GenerateBulkSuratJalanJob::class);
});

test('cetak surat jalan satuan tetap streaming sinkron setelah refactor blade', function () {
    $penjualan = ($this->buatPenjualan)();

    $res = get(route('surat-jalan', $penjualan));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('route unduhan menolak akses tanpa tanda tangan URL', function () {
    get(route('unduhan.surat-jalan', ['file' => 'apa-saja.pdf']))
        ->assertForbidden();
});
