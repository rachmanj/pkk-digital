<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('struktur_pengurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('unit');
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->string('rt')->nullable();
            $table->string('jabatan');
            $table->string('nama');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['kelurahan_id', 'unit']);
            $table->index('pokja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('struktur_pengurus');
    }
};
