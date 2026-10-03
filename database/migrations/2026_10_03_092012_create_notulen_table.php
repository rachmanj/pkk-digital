<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notulen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->unique()->constrained('kegiatan')->cascadeOnDelete();
            $table->string('macam_rapat')->nullable();
            $table->integer('jumlah_diundang')->nullable();
            $table->integer('jumlah_hadir')->nullable();
            $table->integer('jumlah_tidak_hadir')->nullable();
            $table->text('uraian_jalannya')->nullable();
            $table->text('keputusan')->nullable();
            $table->text('lain_lain')->nullable();
            $table->text('penutup')->nullable();
            $table->foreignId('pembuat_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tempat_tanggal_ttd')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notulen');
    }
};
