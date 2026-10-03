<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('jenis');
            $table->string('pos');
            $table->date('tanggal');
            $table->string('sumber_dana')->nullable();
            $table->text('uraian');
            $table->string('no_bukti')->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kelurahan_id', 'tahun'], 'kas_trx_kel_tahun_idx');
            $table->index('pokja_id');
            $table->index('tanggal');
            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
    }
};
