<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->date('tanggal_tutup');
            $table->decimal('sisa_bank', 15, 2)->default(0);
            $table->decimal('sisa_tunai', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['kelurahan_id', 'pokja_id', 'tahun', 'tanggal_tutup'],
                'kas_tutup_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_tutup_buku');
    }
};
