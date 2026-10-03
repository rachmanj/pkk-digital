<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_surat_id')->constrained('agenda_surat')->cascadeOnDelete();
            $table->foreignId('pokja_id')->nullable()->constrained('pokja')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instruksi')->nullable();
            $table->string('status');
            $table->date('tenggat')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->foreignId('oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
