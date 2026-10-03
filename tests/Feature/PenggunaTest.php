<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
