<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Pokja;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_seeder_creates_gsi_kelurahan_and_four_pokja(): void
    {
        $this->seed(MasterSeeder::class);

        $this->assertDatabaseCount('kelurahan', 1);
        $this->assertDatabaseHas('kelurahan', [
            'kode' => 'GSI',
            'nama' => 'Gunung Sari Ilir',
        ]);

        $kelurahan = Kelurahan::where('kode', 'GSI')->first();
        $this->assertNotNull($kelurahan);
        $this->assertSame(4, Pokja::where('kelurahan_id', $kelurahan->id)->count());

        foreach (['I', 'II', 'III', 'IV'] as $kode) {
            $this->assertDatabaseHas('pokja', [
                'kelurahan_id' => $kelurahan->id,
                'kode' => $kode,
                'nama' => 'Pokja '.$kode,
            ]);
        }
    }

    public function test_master_seeder_is_idempotent(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(MasterSeeder::class);

        $this->assertDatabaseCount('kelurahan', 1);
        $this->assertSame(4, Pokja::count());
    }

    public function test_application_timezone_is_asia_makassar(): void
    {
        $this->assertSame('Asia/Makassar', config('app.timezone'));
    }
}
