<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_dashboard_with_kelurahan_and_pokja(): void
    {
        $this->seed(MasterSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Gunung Sari Ilir', false);
        $response->assertSee('Buku PKK Digital', false);
        $response->assertDontSee('AdminLTE', false);
        foreach (['I', 'II', 'III', 'IV'] as $kode) {
            $response->assertSee($kode, false);
        }
    }

    public function test_guest_visiting_home_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
