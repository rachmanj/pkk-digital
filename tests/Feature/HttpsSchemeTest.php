<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class HttpsSchemeTest extends TestCase
{
    public function test_root_redirect_location_uses_https_when_forwarded_proto_is_https(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.1',
            'X-Forwarded-Host' => 'pkk-digital.sbs',
        ])->get('/');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringStartsWith('https://', $location);
        $this->assertStringNotContainsString('http://', $location);
    }

    public function test_login_form_action_uses_https_when_production_forces_https_scheme(): void
    {
        config(['app.url' => 'https://pkk-digital.sbs']);
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->boot();

        $loginUrl = route('login');
        $this->assertStringStartsWith('https://', $loginUrl);

        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('action="'.$loginUrl.'"', false);
    }
}
