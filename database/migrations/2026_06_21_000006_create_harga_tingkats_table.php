<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harga_tingkats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_id')->constrained('satuan_produks')->cascadeOnDelete();
            $table->integer('min_qty'); // qty minimum (dalam satuan terkait) agar harga ini berlaku
            $table->decimal('harga', 15, 2); // harga per satuan pada tingkat ini
            $table->timestamps();

            $table->unique(['satuan_id', 'min_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_tingkats');
    }
};
