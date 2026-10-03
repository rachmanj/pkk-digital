<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
        });

        $reserved = [];
        $users = DB::table('users')->orderBy('id')->get();

        foreach ($users as $user) {
            $base = strtolower(Str::before((string) $user->email, '@'));
            if ($base === '') {
                $base = 'user';
            }

            $candidate = $base;
            $suffix = 1;

            while (
                in_array($candidate, $reserved, true)
                || DB::table('users')
                    ->where('username', $candidate)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $candidate = $base.$suffix;
                $suffix++;
            }

            $reserved[] = $candidate;

            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
