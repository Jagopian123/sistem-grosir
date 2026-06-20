<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            // subtotal = jumlah subtotal semua item, sebelum diskon nota.
            $table->decimal('subtotal', 15, 2)->default(0)->after('tanggal');
            // diskon_tipe: null = tanpa diskon. Dipetakan ke App\Enums\DiscountType.
            $table->string('diskon_tipe')->nullable()->after('subtotal');
            // diskon_nilai: input mentah kasir (persen 0–100 atau rupiah nominal).
            $table->decimal('diskon_nilai', 15, 2)->default(0)->after('diskon_tipe');
            // diskon_nominal: potongan rupiah final yang di-"foto" (hasil hitung, clamp ≤ subtotal).
            $table->decimal('diskon_nominal', 15, 2)->default(0)->after('diskon_nilai');
            // total (kolom lama) tetap menyimpan nilai bersih = subtotal − diskon_nominal.
        });
    }

    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'diskon_tipe', 'diskon_nilai', 'diskon_nominal']);
        });
    }
};
