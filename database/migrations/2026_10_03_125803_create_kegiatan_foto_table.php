<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->string('nama_asli');
            $table->string('file_path');
            $table->string('keterangan')->nullable();
            $table->unsignedSmallInteger('urut');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('kegiatan_id');
            $table->index('urut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_foto');
    }
};
