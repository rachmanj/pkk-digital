<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('kode', 16)->nullable();
            $table->string('program')->nullable();
            $table->string('kegiatan');
            $table->date('tanggal_kegiatan')->nullable();
            $table->text('tujuan')->nullable();
            $table->text('sasaran')->nullable();
            $table->string('tempat')->nullable();
            $table->string('sumber_dana')->nullable();
            $table->text('keterangan')->nullable();
            $table->json('bulan_rencana')->nullable();
            $table->json('bulan_pelaksanaan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kelurahan_id', 'tahun']);
            $table->index('pokja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kerja');
    }
};
