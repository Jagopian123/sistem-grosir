<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice')->unique();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->restrictOnDelete();
            $table->foreignId('sopir_id')->nullable()->constrained('sopirs')->nullOnDelete();
            $table->datetime('tanggal')->index();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('metode_bayar'); // PaymentMethod enum
            $table->string('status_kirim')->index(); // DeliveryStatus enum
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualans');
    }
};
