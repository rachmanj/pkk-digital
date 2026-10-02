<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pokja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('kode', 3);
            $table->string('nama');
            $table->timestamps();

            $table->unique(['kelurahan_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pokja');
    }
};
