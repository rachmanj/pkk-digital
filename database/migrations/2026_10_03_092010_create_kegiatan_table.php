<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('rt')->nullOnDelete();
            $table->string('nama');
            $table->string('jenis');
            $table->date('tanggal');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('tempat');
            $table->string('acara');
            $table->text('uraian')->nullable();
            $table->foreignId('pimpinan_rapat_id')->nullable()->constrained('orang')->nullOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
