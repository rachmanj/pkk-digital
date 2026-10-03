<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_webmanifest_returns_json_with_required_fields(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Buku PKK Digital', $data['name']);
        $this->assertSame('/', $data['start_url']);
        $this->assertSame('standalone', $data['display']);

        $icons = $data['icons'] ?? [];
        $this->assertNotEmpty($icons);

        $has192 = false;
        $has512Maskable = false;
        foreach ($icons as $icon) {
            if (($icon['sizes'] ?? '') === '192x192') {
                $has192 = true;
            }
            if (($icon['sizes'] ?? '') === '512x512' && ($icon['purpose'] ?? '') === 'maskable') {
                $has512Maskable = true;
            }
        }

        $this->assertTrue($has192);
        $this->assertTrue($has512Maskable);
    }

    public function test_service_worker_is_served_as_javascript_with_privacy_rules(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertIsString($contentType);
        $this->assertStringContainsString('javascript', strtolower($contentType));

        $body = $response->getContent();
        $this->assertStringContainsString('/offline.html', $body);
        $this->assertStringContainsString('jangan pernah menyimpan', $body);
        $this->assertStringContainsString('butuh login', $body);
    }

    public function test_offline_page_is_available_in_indonesian(): void
    {
        $response = $this->get('/offline.html');

        $response->assertOk();
        $response->assertSee('Tidak ada koneksi internet', false);
    }

    public function test_pwa_icons_exist_with_expected_dimensions_and_png_type(): void
    {
        $expectations = [
            'icon-192.png' => [192, 192],
            'icon-512.png' => [512, 512],
            'icon-maskable-512.png' => [512, 512],
            'apple-touch-icon.png' => [180, 180],
            'favicon-32.png' => [32, 32],
        ];

        foreach ($expectations as $filename => [$width, $height]) {
            $path = public_path('icons/'.$filename);
            $this->assertFileExists($path);

            $info = getimagesize($path);
            $this->assertIsArray($info);
            $this->assertSame($width, $info[0]);
            $this->assertSame($height, $info[1]);
            $this->assertSame('image/png', $info['mime']);
        }
    }

    public function test_login_layout_includes_pwa_head_and_script(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('href="/manifest.webmanifest"', false);
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('content="#0f766e"', false);
        $response->assertSee('/js/pwa.js', false);
    }

    public function test_authenticated_layout_includes_pwa_head_and_script(): void
    {
        $this->seed(MasterSeeder::class);

        $user = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('href="/manifest.webmanifest"', false);
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('content="#0f766e"', false);
        $response->assertSee('/js/pwa.js', false);
        $response->assertSee('id="pwa-install-btn"', false);
    }
}
