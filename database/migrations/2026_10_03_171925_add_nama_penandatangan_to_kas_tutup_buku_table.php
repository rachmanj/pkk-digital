<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kas_tutup_buku', function (Blueprint $table) {
            $table->string('nama_ketua')->nullable()->after('catatan');
            $table->string('nama_bendahara')->nullable()->after('nama_ketua');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_tutup_buku', function (Blueprint $table) {
            $table->dropColumn(['nama_ketua', 'nama_bendahara']);
        });
    }
};
