<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_saldo_awal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('pos');
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(
                ['kelurahan_id', 'pokja_id', 'tahun', 'pos'],
                'kas_saldo_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_saldo_awal');
    }
};
