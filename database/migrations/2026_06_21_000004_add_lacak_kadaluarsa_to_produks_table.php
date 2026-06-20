<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            // Hanya produk bertanda (mis. minyak, gula) yang mewajibkan input
            // tanggal kadaluarsa/batch saat stok masuk.
            $table->boolean('lacak_kadaluarsa')->default(false)->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->dropColumn('lacak_kadaluarsa');
        });
    }
};
