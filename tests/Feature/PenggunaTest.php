<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_duplikat_ditolak_saat_membuat_pengguna(): void
    {
        $this->seed(MasterSeeder::class);
        $admin = $this->actingAdmin();

        User::factory()->create([
            'username' => 'dupe',
            'email' => 'dupe-a@pkk.test',
        ]);

        $jumlahSebelum = User::query()->count();

        $response = $this->actingAs($admin)->post(route('pengguna.store'), [
            'name' => 'Pengguna Duplikat',
            'username' => 'dupe',
            'email' => 'dupe-b@pkk.test',
            'password' => 'password123',
            'role' => 'kader',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertSame($jumlahSebelum, User::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'dupe-b@pkk.test']);
    }

    public function test_username_terlalu_pendek_ditolak(): void
    {
        $this->seed(MasterSeeder::class);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->post(route('pengguna.store'), [
            'name' => 'Nama Pendek',
            'username' => 'ab',
            'email' => 'pendek@pkk.test',
            'password' => 'password123',
            'role' => 'kader',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', ['email' => 'pendek@pkk.test']);
    }

    public function test_superadmin_dapat_menetapkan_sandi_saat_membuat_pengguna(): void
    {
        $this->seed(MasterSeeder::class);
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->post(route('pengguna.store'), [
            'name' => 'Pengguna Baru',
            'username' => 'baru',
            'email' => 'baru@pkk.test',
            'password' => 'sandibaru123',
            'role' => 'kader',
        ]);

        $response->assertRedirect(route('pengguna.index'));

        $user = User::query()->where('username', 'baru')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('sandibaru123', $user->password));
    }

    public function test_superadmin_dapat_mengatur_ulang_sandi_lewat_edit(): void
    {
        $this->seed(MasterSeeder::class);
        $admin = $this->actingAdmin();

        $target = User::factory()->create([
            'username' => 'target',
            'email' => 'target@pkk.test',
            'password' => 'password-lama',
        ]);
        $target->assignRole('kader');

        $response = $this->actingAs($admin)->put(route('pengguna.update', $target), [
            'name' => $target->name,
            'username' => $target->username,
            'email' => $target->email,
            'password' => 'reset12345',
            'role' => 'kader',
        ]);

        $response->assertRedirect(route('pengguna.index'));

        $target->refresh();
        $this->assertTrue(Hash::check('reset12345', $target->password));
        $this->assertFalse(Hash::check('password-lama', $target->password));
    }

    public function test_superadmin_edit_kosongkan_sandi_tidak_mengubah_sandi(): void
    {
        $this->seed(MasterSeeder::class);
        $admin = $this->actingAdmin();

        $target = User::factory()->create([
            'username' => 'tetap',
            'email' => 'tetap@pkk.test',
            'password' => 'password-lama',
        ]);
        $target->assignRole('kader');

        $this->actingAs($admin)->put(route('pengguna.update', $target), [
            'name' => 'Nama Diubah',
            'username' => 'tetap',
            'email' => 'tetap@pkk.test',
            'password' => '',
            'role' => 'kader',
        ])->assertRedirect(route('pengguna.index'));

        $target->refresh();
        $this->assertSame('Nama Diubah', $target->name);
        $this->assertTrue(Hash::check('password-lama', $target->password));
    }

    public function test_pengguna_biasa_tidak_bisa_membuka_modul_pengguna(): void
    {
        $this->seed(MasterSeeder::class);
        $kader = $this->userForRole('kader', [
            'username' => 'kaderbiasa',
            'email' => 'kaderbiasa@pkk.test',
        ]);

        $this->actingAs($kader)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($kader)->get(route('pengguna.create'))->assertForbidden();
    }
}
