<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku_tamu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('no_urut_tahun');
            $table->date('tanggal');
            $table->string('nama_tamu');
            $table->string('alamat')->nullable();
            $table->string('keperluan')->nullable();
            $table->string('tujuan')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(
                ['kelurahan_id', 'pokja_id', 'tahun', 'no_urut_tahun'],
                'buku_tamu_uq_no'
            );
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buku_tamu');
    }
};
