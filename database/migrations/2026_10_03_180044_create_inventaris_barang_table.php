<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('nama_barang');
            $table->string('asal_barang')->nullable();
            $table->date('tanggal_terima');
            $table->unsignedInteger('jumlah');
            $table->string('tempat_penyimpanan')->nullable();
            $table->string('kondisi');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kelurahan_id', 'tahun']);
            $table->index('pokja_id');
            $table->index('kondisi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris_barang');
    }
};
