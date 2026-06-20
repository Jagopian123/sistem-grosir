<?php

declare(strict_types=1);

use App\Actions\Sales\CreateSaleAction;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\SatuanProduk;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $owner = User::factory()->create();
    $owner->assignRole(Role::Owner->value);
    actingAs($owner);

    $produk = Produk::factory()->create(['stok' => 100]);
    $satuan = SatuanProduk::factory()->create([
        'produk_id' => $produk->id,
        'konversi' => 24,
        'harga_jual' => 120_000,
    ]);

    $this->penjualan = app(CreateSaleAction::class)->execute(
        pelanggan: Pelanggan::factory()->create(),
        items: [['satuan_id' => $satuan->id, 'qty' => 2]],
        metode: PaymentMethod::Tunai,
    );
});

test('invoice A4 dapat dicetak sebagai PDF', function () {
    $res = get(route('invoice', [$this->penjualan, 'a4']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('struk thermal 58mm dapat dicetak sebagai PDF', function () {
    $res = get(route('invoice', [$this->penjualan, 'thermal58']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('struk thermal 80mm dapat dicetak sebagai PDF', function () {
    $res = get(route('invoice', [$this->penjualan, 'thermal80']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('format default tanpa parameter menghasilkan invoice A4', function () {
    $res = get(route('invoice', $this->penjualan));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

test('format tidak dikenal ditolak (404)', function () {
    get(route('invoice', [$this->penjualan, 'a4']).'/ngawur')->assertNotFound();
});

test('cetak invoice tidak mengubah stok maupun buku besar mutasi', function () {
    $produk = $this->penjualan->details->first()->produk;
    $stokSebelum = $produk->stok;
    $mutasiSebelum = $produk->mutasiStok()->count();

    get(route('invoice', [$this->penjualan, 'a4']))->assertOk();
    get(route('invoice', [$this->penjualan, 'thermal58']))->assertOk();
    get(route('invoice', [$this->penjualan, 'thermal80']))->assertOk();

    expect($produk->fresh()->stok)->toBe($stokSebelum)
        ->and($produk->mutasiStok()->count())->toBe($mutasiSebelum);
});

test('cetak invoice butuh autentikasi (tamu tidak mendapat PDF)', function () {
    app('auth')->logout();

    $res = get(route('invoice', [$this->penjualan, 'a4']));

    // Middleware auth memblokir tamu: status apa pun selain 200/PDF dianggap benar.
    expect($res->status())->not->toBe(200);
});
