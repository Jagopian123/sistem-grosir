<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('no_opname')->unique();
            $table->datetime('tanggal')->index();
            $table->integer('total_selisih')->default(0); // net (satuan dasar): + lebih, - kurang
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('detail_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->restrictOnDelete();
            $table->integer('stok_sistem'); // snapshot stok sebelum penyesuaian (satuan dasar)
            $table->integer('stok_fisik');  // hasil hitung fisik (satuan dasar)
            $table->integer('selisih');     // stok_fisik - stok_sistem
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_stock_opnames');
        Schema::dropIfExists('stock_opnames');
    }
};
