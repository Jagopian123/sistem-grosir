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
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_toko');
            $table->string('nama_kontak')->nullable();
            $table->string('telepon');
            $table->text('alamat');
            $table->timestamps();

            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText('nama_toko');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
