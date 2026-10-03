<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PkkPermission;
use Database\Seeders\MasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_menjalankan_seeder_dua_kali_tidak_mengubah_password_pengguna_yang_sudah_ada(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $customPassword = 'sandi-kustom-uji-seeder';
        $admin->password = Hash::make($customPassword);
        $admin->save();
        $hashBefore = $admin->fresh()->password;

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin->refresh();
        $this->assertSame($hashBefore, $admin->password);
        $this->assertTrue(Hash::check($customPassword, $admin->password));
    }

    public function test_pengguna_baru_mendapat_sandi_dari_env_saat_pertama_dibuat(): void
    {
        $this->seed(MasterSeeder::class);

        $this->assertNull(User::query()->where('username', 'bendahara')->first());

        $this->seed(RolePermissionSeeder::class);

        $bendahara = User::query()->where('username', 'bendahara')->firstOrFail();
        $expectedPassword = env('ADMIN_PASSWORD', 'password');
        $this->assertTrue(Hash::check($expectedPassword, $bendahara->password));
    }

    public function test_peran_tetap_disinkronkan_setelah_seeder_dijalankan_ulang(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $kader = User::query()->where('username', 'kader')->firstOrFail();
        $kader->syncRoles(['superadmin']);

        $this->assertTrue($kader->hasRole('superadmin'));

        $this->seed(RolePermissionSeeder::class);

        $kader->refresh();
        $this->assertTrue($kader->hasRole('kader'));
        $this->assertFalse($kader->hasRole('superadmin'));
    }

    public function test_pengguna_bendahara_dari_seeder_memiliki_peran_dan_izin_kas(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $bendahara = User::query()->where('username', 'bendahara')->firstOrFail();
        $this->assertSame('bendahara@pkk.test', $bendahara->email);
        $this->assertSame('Bendahara TP PKK', $bendahara->name);
        $this->assertNull($bendahara->pokja_id);
        $this->assertTrue($bendahara->hasRole('bendahara'));
        $this->assertTrue($bendahara->can(PkkPermission::KELOLA_KAS));
        $this->assertTrue($bendahara->can(PkkPermission::LIHAT_KAS));
    }
}
