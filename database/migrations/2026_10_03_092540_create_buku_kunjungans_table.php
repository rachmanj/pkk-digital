<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('no_urut_tahun');
            $table->date('tanggal');
            $table->foreignId('orang_id')->nullable()->constrained('orang')->nullOnDelete();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('lokasi_kunjungan')->nullable();
            $table->string('jenis_kegiatan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(
                ['kelurahan_id', 'tahun', 'no_urut_tahun'],
                'buku_kunjung_uq_no'
            );
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_kunjungan');
    }
};
