<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-buku besar batch: tiap stok masuk untuk produk yang dilacak kadaluarsanya
 * menulis satu baris batch (qty dalam satuan dasar). Stok keluar mengurangi
 * qty_sisa secara FEFO (First Expired First Out). Total qty_sisa per produk
 * konsisten dengan stok yang masuk lewat batch, dan buku besar mutasi_stok
 * tetap menjadi sumber kebenaran untuk produk.stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_stoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->restrictOnDelete();
            $table->string('kode_batch')->nullable();
            $table->date('tanggal_kadaluarsa')->nullable();
            $table->integer('qty_masuk');          // satuan dasar saat masuk
            $table->integer('qty_sisa');           // satuan dasar tersisa (dikurangi FEFO)
            $table->string('sumber');              // referensi, mis. "pembelian:12"
            $table->date('tanggal_masuk');
            $table->timestamps();

            // FEFO & alert ED per produk: urut tanggal_kadaluarsa.
            $table->index(['produk_id', 'tanggal_kadaluarsa']);
            // Alert global mendekati ED.
            $table->index('tanggal_kadaluarsa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_stoks');
    }
};
