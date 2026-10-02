<?php

namespace Tests\Unit;

use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\Rt;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelurahanTest extends TestCase
{
    use RefreshDatabase;

    public function test_kelurahan_has_many_pokja(): void
    {
        $kelurahan = Kelurahan::factory()->create();

        Pokja::factory()->create([
            'kelurahan_id' => $kelurahan->id,
            'kode' => 'I',
            'nama' => 'Pokja I',
        ]);

        $kelurahan->load('pokja');

        $this->assertCount(1, $kelurahan->pokja);
        $this->assertSame($kelurahan->id, $kelurahan->pokja->first()->kelurahan_id);
    }

    public function test_kelurahan_has_many_rt(): void
    {
        $kelurahan = Kelurahan::factory()->create();

        Rt::factory()->create([
            'kelurahan_id' => $kelurahan->id,
            'nomor' => '01',
        ]);

        $this->assertCount(1, $kelurahan->fresh()->rt);
    }

    public function test_kelurahan_kode_must_be_unique(): void
    {
        Kelurahan::factory()->create(['kode' => 'UNIQ01']);

        $this->expectException(QueryException::class);

        Kelurahan::factory()->create(['kode' => 'UNIQ01']);
    }

    public function test_pokja_kode_unique_per_kelurahan(): void
    {
        $kelurahan = Kelurahan::factory()->create();

        Pokja::factory()->create([
            'kelurahan_id' => $kelurahan->id,
            'kode' => 'I',
            'nama' => 'Pokja I',
        ]);

        $this->expectException(QueryException::class);

        Pokja::factory()->create([
            'kelurahan_id' => $kelurahan->id,
            'kode' => 'I',
            'nama' => 'Pokja I duplikat',
        ]);
    }

    public function test_pokja_kode_may_repeat_across_different_kelurahan(): void
    {
        $kelurahanA = Kelurahan::factory()->create();
        $kelurahanB = Kelurahan::factory()->create();

        Pokja::factory()->create([
            'kelurahan_id' => $kelurahanA->id,
            'kode' => 'I',
            'nama' => 'Pokja I A',
        ]);

        $pokjaB = Pokja::factory()->create([
            'kelurahan_id' => $kelurahanB->id,
            'kode' => 'I',
            'nama' => 'Pokja I B',
        ]);

        $this->assertModelExists($pokjaB);
    }
}
