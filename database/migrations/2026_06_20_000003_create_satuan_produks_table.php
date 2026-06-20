<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuan_produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->string('nama_satuan');
            $table->integer('konversi')->default(1);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['produk_id', 'nama_satuan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan_produks');
    }
};
