<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_login_fails_with_wrong_password_and_shows_error(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'username' => 'Nama pengguna atau kata sandi salah.',
        ]);
        $this->assertGuest();
    }

    public function test_login_succeeds_with_username_and_redirects_to_dashboard(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_with_email_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'username' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'username' => 'Nama pengguna atau kata sandi salah.',
        ]);
        $this->assertGuest();
    }

    public function test_login_username_is_case_insensitive(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'username' => '  ADMIN  ',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
