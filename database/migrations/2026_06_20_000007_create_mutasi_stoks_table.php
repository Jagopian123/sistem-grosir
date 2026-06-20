<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->restrictOnDelete();
            $table->string('tipe'); // StockMovementType enum
            $table->integer('qty'); // dalam satuan dasar
            $table->string('referensi'); // mis. "penjualan:12"
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
            $table->timestamps();

            $table->index('tipe');
            $table->index(['produk_id', 'created_at']); // composite: riwayat per produk
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stoks');
    }
};
