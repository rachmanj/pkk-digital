<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Presensi;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CetakTest extends TestCase
{
    use RefreshDatabase;

    private function seedMaster(): Kelurahan
    {
        $this->seed(MasterSeeder::class);

        return Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
    }

    public function test_setiap_kode_buku_di_konfigurasi_mengembalikan_200_dan_label_kolom_pertama(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        foreach (array_keys(config('buku')) as $kode) {
            $params = ['tahun' => 2026];
            if (in_array($kode, ['daftar_hadir', 'notulen'], true)) {
                $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
                $kegiatan = Kegiatan::query()->create([
                    'kelurahan_id' => $kelurahan->id,
                    'nama' => 'Kegiatan Cetak Uji',
                    'jenis' => Kegiatan::JENIS_RAPAT,
                    'tanggal' => '2026-06-01',
                    'tempat' => 'Aula',
                    'acara' => 'Uji cetak',
                ]);
                $params['kegiatan'] = $kegiatan->id;
            }

            $response = $this->actingAs($user)->get(route('cetak.show', ['buku' => $kode] + $params));
            $response->assertOk();

            $config = config('buku.'.$kode);
            if ($config['tipe'] === 'notulen') {
                $response->assertSee('Nama kegiatan', false);
            } else {
                $firstLabel = $config['kolom'][0]['label'];
                $response->assertSee($firstLabel, false);
            }
        }
    }

    public function test_urutan_label_kolom_pada_halaman_cetak_sesuai_konfigurasi(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $kode = 'buku_tamu';
        $labels = array_column(config('buku.'.$kode)['kolom'], 'label');

        $response = $this->actingAs($user)->get(route('cetak.show', [
            'buku' => $kode,
            'tahun' => 2026,
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $positions = [];
        foreach ($labels as $label) {
            $pos = strpos($content, $label);
            $this->assertNotFalse($pos, "Label {$label} tidak ditemukan pada halaman cetak.");
            $positions[] = $pos;
        }

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Urutan label kolom pada halaman cetak tidak sesuai konfigurasi.');
    }

    public function test_kode_buku_tidak_dikenal_mengembalikan_404(): void
    {
        $user = $this->actingAdmin();

        $this->actingAs($user)->get(route('cetak.show', ['buku' => 'buku_tidak_ada']))->assertNotFound();
        $this->actingAs($user)->get(route('cetak.pdf', ['buku' => 'buku_tidak_ada']))->assertNotFound();
        $this->actingAs($user)->get(route('export.buku', ['buku' => 'buku_tidak_ada']))->assertNotFound();
    }

    public function test_cetak_daftar_hadir_hanya_peserta_hadir_kegiatan_terpilih(): void
    {
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();

        $kegiatanA = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Alpha',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-03-01',
            'tempat' => 'Aula',
            'acara' => 'A',
        ]);
        $kegiatanB = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Beta',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-03-02',
            'tempat' => 'Aula',
            'acara' => 'B',
        ]);

        $hadirAlpha = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Peserta Hadir Alpha']);
        $tidakHadirAlpha = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Peserta Tidak Hadir Alpha']);
        $hadirBeta = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Peserta Hadir Beta']);

        Presensi::query()->create([
            'kegiatan_id' => $kegiatanA->id,
            'orang_id' => $hadirAlpha->id,
            'urut' => 1,
            'hadir' => true,
        ]);
        Presensi::query()->create([
            'kegiatan_id' => $kegiatanA->id,
            'orang_id' => $tidakHadirAlpha->id,
            'urut' => 2,
            'hadir' => false,
        ]);
        Presensi::query()->create([
            'kegiatan_id' => $kegiatanB->id,
            'orang_id' => $hadirBeta->id,
            'urut' => 1,
            'hadir' => true,
        ]);

        $response = $this->actingAs($user)->get(route('cetak.show', [
            'buku' => 'daftar_hadir',
            'kegiatan' => $kegiatanA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Peserta Hadir Alpha', false);
        $response->assertDontSee('Peserta Hadir Beta', false);
        $response->assertDontSee('Peserta Tidak Hadir Alpha', false);
    }

    public function test_export_mengembalikan_200_dan_baris_label_kolom_pertama(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $kode = 'daftar_anggota';
        $firstLabel = config('buku.'.$kode)['kolom'][0]['label'];

        $response = $this->actingAs($user)->get(route('export.buku', [
            'buku' => $kode,
            'tahun' => 2026,
        ]));

        $response->assertOk();

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);

        $sheet = IOFactory::load($file->getPathname())->getActiveSheet();
        $this->assertSame($firstLabel, $sheet->getCell('A1')->getValue());
    }

    public function test_tamu_dialihkan_ke_login_pada_rute_cetak_dan_export(): void
    {
        $this->get(route('cetak.show', ['buku' => 'buku_tamu']))->assertRedirect(route('login'));
        $this->get(route('cetak.pdf', ['buku' => 'buku_tamu']))->assertRedirect(route('login'));
        $this->get(route('export.buku', ['buku' => 'buku_tamu']))->assertRedirect(route('login'));
    }
}
