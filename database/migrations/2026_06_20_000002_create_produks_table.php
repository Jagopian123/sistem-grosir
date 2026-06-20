<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategoris')->restrictOnDelete();
            $table->string('nama');
            $table->string('satuan_dasar');
            $table->integer('stok')->default(0);
            $table->integer('stok_min')->default(0);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            // index aktif terpisah karena FK kategori_id sudah otomatis dapat index
            $table->index('aktif');
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText('nama');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
