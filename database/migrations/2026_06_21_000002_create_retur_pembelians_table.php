<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur')->unique();
            $table->foreignId('pembelian_id')->constrained('pembelians')->restrictOnDelete();
            $table->datetime('tanggal')->index();
            $table->decimal('total', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_retur_pembelians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_pembelian_id')->constrained('retur_pembelians')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->restrictOnDelete();
            $table->integer('qty'); // dalam satuan dasar
            $table->decimal('harga_beli', 15, 2); // snapshot dari pembelian asli
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_retur_pembelians');
        Schema::dropIfExists('retur_pembelians');
    }
};
