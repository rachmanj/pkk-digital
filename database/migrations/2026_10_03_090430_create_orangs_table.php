<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jenis_kelamin', 1);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('rt_id')->nullable()->constrained('rt')->nullOnDelete();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('foto_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('nama');
            $table->index('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang');
    }
};
