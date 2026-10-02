<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('nomor', 10);
            $table->string('dasawisma')->nullable();
            $table->unsignedBigInteger('ketua_orang_id')->nullable();
            $table->timestamps();

            $table->unique(['kelurahan_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rt');
    }
};
