<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->index('kelurahan_id', 'agenda_surat_kel_idx');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->dropUnique('agenda_surat_uq_no');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->unsignedSmallInteger('tahun')->nullable()->after('no_urut_tahun');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('UPDATE agenda_surat SET tahun = YEAR(tanggal_surat)');
            DB::statement('ALTER TABLE agenda_surat MODIFY tahun SMALLINT UNSIGNED NOT NULL');
        } else {
            DB::statement("UPDATE agenda_surat SET tahun = CAST(strftime('%Y', tanggal_surat) AS INTEGER)");
            Schema::table('agenda_surat', function (Blueprint $table) {
                $table->unsignedSmallInteger('tahun')->nullable(false)->change();
            });
        }

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->unique(
                ['kelurahan_id', 'jenis', 'pokja_id', 'tahun', 'no_urut_tahun'],
                'agenda_surat_uq_no'
            );
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->dropIndex('agenda_surat_kel_idx');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->index('kelurahan_id', 'agenda_surat_kel_idx');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->dropUnique('agenda_surat_uq_no');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->dropIndex('agenda_surat_kel_idx');
        });

        Schema::table('agenda_surat', function (Blueprint $table) {
            $table->unique(
                ['kelurahan_id', 'jenis', 'pokja_id', 'no_urut_tahun'],
                'agenda_surat_uq_no'
            );
        });
    }
};
