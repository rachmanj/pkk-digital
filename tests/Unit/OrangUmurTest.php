<?php

namespace Tests\Unit;

use App\Models\Orang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrangUmurTest extends TestCase
{
    use RefreshDatabase;

    public function test_umur_dihitung_dari_tanggal_lahir(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $orang = Orang::factory()->create([
            'tanggal_lahir' => '1986-03-10',
        ]);

        $this->assertSame(40, $orang->umur);
    }

    public function test_umur_null_jika_tanggal_lahir_kosong(): void
    {
        $orang = Orang::factory()->create([
            'tanggal_lahir' => null,
        ]);

        $this->assertNull($orang->umur);
    }
}
