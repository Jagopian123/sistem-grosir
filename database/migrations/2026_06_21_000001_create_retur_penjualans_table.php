<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur')->unique();
            $table->foreignId('penjualan_id')->constrained('penjualans')->restrictOnDelete();
            $table->datetime('tanggal')->index();
            $table->decimal('total', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_retur_penjualans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_penjualan_id')->constrained('retur_penjualans')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->restrictOnDelete();
            $table->foreignId('satuan_id')->constrained('satuan_produks')->restrictOnDelete();
            $table->integer('qty'); // dalam satuan yang dipilih
            $table->decimal('harga_satuan', 15, 2); // snapshot dari penjualan asli
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_retur_penjualans');
        Schema::dropIfExists('retur_penjualans');
    }
};
