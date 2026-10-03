<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('orang_id')->nullable()->constrained('orang')->nullOnDelete();
            $table->string('nama_manual')->nullable();
            $table->string('alamat_manual')->nullable();
            $table->string('jabatan_manual')->nullable();
            $table->unsignedInteger('urut');
            $table->boolean('hadir')->default(true);
            $table->string('keterangan')->nullable();
            $table->foreignId('oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('kegiatan_id');
            $table->index('urut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
