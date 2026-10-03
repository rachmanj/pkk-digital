<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keanggotaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orang_id')->constrained('orang')->cascadeOnDelete();
            $table->foreignId('kelurahan_id')->constrained('kelurahan')->cascadeOnDelete();
            $table->string('jenis');
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->string('jabatan')->nullable();
            $table->string('no_registrasi')->nullable();
            $table->string('sk_nomor')->nullable();
            $table->date('mulai')->nullable();
            $table->date('selesai')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['kelurahan_id', 'no_registrasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keanggotaan');
    }
};
