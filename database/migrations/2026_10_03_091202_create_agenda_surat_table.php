<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('jenis');
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedInteger('no_urut_tahun');
            $table->date('tanggal_surat');
            $table->date('tanggal_terima')->nullable();
            $table->string('no_surat');
            $table->string('dari')->nullable();
            $table->string('kepada')->nullable();
            $table->string('perihal');
            $table->string('lampiran')->nullable();
            $table->string('tembusan')->nullable();
            $table->string('file_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(
                ['kelurahan_id', 'jenis', 'pokja_id', 'no_urut_tahun'],
                'agenda_surat_uq_no'
            );
            $table->index('tanggal_surat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_surat');
    }
};
