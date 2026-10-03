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
        $this->assertStringContainsString("CACHE_VERSION = 'pkk-v2'", $body);
        $this->assertStringContainsString("'/build/'", $body);
        $this->assertStringContainsString("'/icons/'", $body);
        $this->assertStringContainsString("'/js/'", $body);
        $this->assertStringContainsString("'/css/'", $body);
        $this->assertStringContainsString("'/fonts/'", $body);
        $this->assertStringNotContainsString('request.destination', $body);
        $this->assertStringNotContainsString('STATIC_DESTINATIONS', $body);
        $this->assertDoesNotMatchRegularExpression(
            '/CACHEABLE_PATH_PREFIXES\s*=\s*\[[^\]]*kegiatan-foto/',
            $body,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/PRECACHE_URLS\s*=\s*\[[^\]]*kegiatan-foto/',
            $body,
        );
    }

    public function test_nginx_host_example_proxies_all_traffic_without_breaking_pwa_locations(): void
    {
        $path = base_path('deploy/production/nginx-host.conf.example');
        $this->assertFileExists($path);

        $config = file_get_contents($path);
        $this->assertIsString($config);

        $activeLines = array_filter(
            explode("\n", $config),
            static fn (string $line): bool => ! str_starts_with(ltrim($line), '#'),
        );
        $activeConfig = implode("\n", $activeLines);

        $this->assertStringNotContainsString('location = /sw.js', $activeConfig);
        $this->assertStringNotContainsString('location = /manifest.webmanifest', $activeConfig);
        $this->assertStringContainsString('location / {', $activeConfig);
        $this->assertStringContainsString('proxy_pass http://127.0.0.1:8080', $activeConfig);
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
