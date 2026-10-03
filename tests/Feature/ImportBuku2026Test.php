<?php

namespace Tests\Feature;

use App\Models\AgendaSurat;
use App\Models\Keanggotaan;
use App\Models\Orang;
use App\Services\Buku2026Importer;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ImportBuku2026Test extends TestCase
{
    use RefreshDatabase;

    private function fixturePath(): string
    {
        return base_path('tests/fixtures/buku2026-mini.xlsx');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterSeeder::class);
    }

    public function test_parse_tanggal_teks_indonesia(): void
    {
        $parsed = Buku2026Importer::parseTanggal('28-Juni-26');

        $this->assertNotNull($parsed);
        $this->assertSame('2026-06-28', $parsed->toDateString());
    }

    public function test_dry_run_tidak_menulis_database(): void
    {
        $beforeOrang = Orang::query()->count();
        $beforeAgenda = AgendaSurat::query()->count();

        $exit = Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame($beforeOrang, Orang::query()->count());
        $this->assertSame($beforeAgenda, AgendaSurat::query()->count());
    }

    public function test_execute_tanpa_force_ditolak(): void
    {
        $exit = Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
            '--execute' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString(
            '--force',
            Artisan::output(),
        );
    }

    public function test_execute_dengan_force_menulis_data(): void
    {
        Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertGreaterThan(0, Orang::query()->count());
        $this->assertGreaterThan(0, AgendaSurat::query()->count());
        $this->assertDatabaseHas('orang', ['nama' => 'BUDI SANJAYA']);
    }

    public function test_import_dua_kali_tidak_menggandakan(): void
    {
        foreach ([1, 2] as $run) {
            Artisan::call('pkk:import-buku2026', [
                '--file' => $this->fixturePath(),
                '--execute' => true,
                '--force' => true,
            ]);
        }

        $this->assertSame(3, Orang::query()->count());
        $this->assertSame(3, AgendaSurat::query()->count());
    }

    public function test_baris_nama_kosong_dilewati(): void
    {
        $importer = new Buku2026Importer(true);
        $report = $importer->run($this->fixturePath());

        $skippedNama = collect($report->skippedRows)
            ->first(fn (array $row) => $row['reason'] === 'nama kosong');

        $this->assertNotNull($skippedNama);
        $this->assertGreaterThan(0, $report->skipped);
    }

    public function test_jenis_keanggotaan_dari_penanda_kedudukan(): void
    {
        Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
            '--execute' => true,
            '--force' => true,
        ]);

        $this->assertDatabaseHas('keanggotaan', [
            'jenis' => Keanggotaan::JENIS_KADER_UMUM,
        ]);

        $this->assertDatabaseHas('keanggotaan', [
            'jenis' => Keanggotaan::JENIS_KADER_KHUSUS,
        ]);
    }

    public function test_nomor_urut_surat_berurutan_per_jenis_dan_tahun(): void
    {
        Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
            '--execute' => true,
            '--force' => true,
        ]);

        $masuk = AgendaSurat::query()
            ->where('jenis', AgendaSurat::JENIS_MASUK)
            ->where('tahun', 2026)
            ->orderBy('no_urut_tahun')
            ->pluck('no_urut_tahun')
            ->all();

        $this->assertSame([1, 2], $masuk);

        $keluar = AgendaSurat::query()
            ->where('jenis', AgendaSurat::JENIS_KELUAR)
            ->where('tahun', 2026)
            ->orderBy('no_urut_tahun')
            ->pluck('no_urut_tahun')
            ->all();

        $this->assertSame([1], $keluar);
    }

    public function test_tanggal_hasil_import_dari_teks_juni(): void
    {
        Artisan::call('pkk:import-buku2026', [
            '--file' => $this->fixturePath(),
            '--execute' => true,
            '--force' => true,
        ]);

        $surat = AgendaSurat::query()
            ->where('no_surat', 'MASUK/002/2026')
            ->first();

        $this->assertNotNull($surat);
        $this->assertSame('2026-06-28', $surat->tanggal_surat->toDateString());
    }
}
