<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UbahSandiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_membuka_halaman_ubah_sandi(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->get(route('ubah-sandi.show'));

        $response->assertOk();
        $response->assertSee('Kata sandi saat ini', false);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $response = $this->get(route('ubah-sandi.show'));

        $response->assertRedirect(route('login'));
    }

    public function test_ubah_sandi_berhasil_dan_sandi_baru_berlaku(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        Activity::query()->where('log_name', 'pengguna')->delete();

        $response = $this->actingAs($user)->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'password-lama',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ]);

        $response->assertRedirect(route('ubah-sandi.show'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('sandibaru123', $user->password));
        $this->assertFalse(Hash::check('password-lama', $user->password));

        $this->assertAuthenticatedAs($user);

        $activity = Activity::query()->where('log_name', 'pengguna')->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame('password_changed', $activity->description);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertStringNotContainsString('sandibaru123', json_encode($activity->properties, JSON_THROW_ON_ERROR));
    }

    public function test_kata_sandi_saat_ini_salah_ditolak(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('ubah-sandi.show'))->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'salah',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ]);

        $response->assertRedirect(route('ubah-sandi.show'));
        $response->assertSessionHasErrors('password_saat_ini');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_konfirmasi_sandi_tidak_sama_ditolak(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('ubah-sandi.show'))->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'password-lama',
            'password' => 'sandibaru123',
            'password_confirmation' => 'beda',
        ]);

        $response->assertRedirect(route('ubah-sandi.show'));
        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_sandi_baru_kurang_dari_8_karakter_ditolak(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('ubah-sandi.show'))->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'password-lama',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]);

        $response->assertRedirect(route('ubah-sandi.show'));
        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_sandi_baru_sama_dengan_sandi_lama_ditolak(): void
    {
        $user = User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $response = $this->actingAs($user)->from(route('ubah-sandi.show'))->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'password-lama',
            'password' => 'password-lama',
            'password_confirmation' => 'password-lama',
        ]);

        $response->assertRedirect(route('ubah-sandi.show'));
        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_login_dengan_sandi_baru_setelah_ubah_sandi(): void
    {
        User::factory()->create([
            'username' => 'kader1',
            'email' => 'kader1@pkk.test',
            'password' => 'password-lama',
        ]);

        $user = User::query()->where('username', 'kader1')->firstOrFail();

        $this->actingAs($user)->post(route('ubah-sandi.update'), [
            'password_saat_ini' => 'password-lama',
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertRedirect(route('ubah-sandi.show'));

        $this->post(route('logout'));

        $response = $this->post(route('login'), [
            'username' => 'kader1',
            'password' => 'sandibaru123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
