<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\StrukturPengurus;
use App\Support\ActiveKelurahan;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StrukturPengurusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kelurahan: Kelurahan, pokjaI: Pokja}
     */
    private function seedMaster(): array
    {
        $this->seed(MasterSeeder::class);
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokjaI = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'I')->firstOrFail();

        return ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'pokja_id' => null,
            'rt' => null,
            'jabatan' => 'KETUA',
            'nama' => 'Nama Uji',
            'urutan' => 0,
            'keterangan' => null,
        ], $overrides);
    }

    public function test_crud_oleh_sekretaris_dan_superadmin(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-struktur@pkk.test']);

        $this->actingAs($sekretaris)->get(route('struktur.index'))->assertOk();
        $this->actingAs($sekretaris)->get(route('struktur.create'))->assertOk();

        $this->actingAs($sekretaris)->post(route('struktur.store'), $this->validPayload([
            'jabatan' => 'PEMBINA',
            'nama' => 'LURAH GSI',
        ]))->assertRedirect();

        $row = StrukturPengurus::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('PEMBINA', $row->jabatan);

        $this->actingAs($sekretaris)->get(route('struktur.show', $row))->assertOk();
        $this->actingAs($sekretaris)->get(route('struktur.edit', $row))->assertOk();

        $this->actingAs($sekretaris)->put(route('struktur.update', $row), $this->validPayload([
            'nama' => 'Nama Diubah',
        ]))->assertRedirect();

        $row->refresh();
        $this->assertSame('Nama Diubah', $row->nama);

        $this->actingAs($sekretaris)->delete(route('struktur.destroy', $row))->assertRedirect();
        $this->assertNull(StrukturPengurus::query()->find($row->id));

        $admin = $this->actingAdmin();
        $this->actingAs($admin)->post(route('struktur.store'), $this->validPayload([
            'unit' => StrukturPengurus::UNIT_POKJA,
            'pokja_id' => $pokjaI->id,
            'jabatan' => 'Ketua Pokja',
            'nama' => 'AGUSTINAH',
        ]))->assertRedirect();
        $this->assertDatabaseHas('struktur_pengurus', [
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaI->id,
            'nama' => 'AGUSTINAH',
        ]);
    }

    public function test_kader_dan_ketua_403_saat_menyimpan(): void
    {
        $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-struktur@pkk.test']);
        $ketua = $this->userForRole('ketua', ['email' => 'ketua-struktur@pkk.test']);

        $this->actingAs($kader)->post(route('struktur.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($ketua)->post(route('struktur.store'), $this->validPayload())->assertForbidden();
    }

    public function test_pengelompokan_per_jabatan_dan_pokja(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'jabatan' => 'KETUA',
            'nama' => 'A',
            'urutan' => 1,
        ]);
        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'jabatan' => 'KETUA',
            'nama' => 'B',
            'urutan' => 2,
        ]);
        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_POKJA,
            'pokja_id' => $pokjaI->id,
            'jabatan' => 'Anggota',
            'nama' => 'C',
            'urutan' => 0,
        ]);

        $rows = StrukturPengurus::query()->where('kelurahan_id', $kelurahan->id)->get();
        $perJabatan = StrukturPengurus::kelompokkanPerJabatan($rows->where('unit', StrukturPengurus::UNIT_TP_PKK));
        $this->assertCount(2, $perJabatan['KETUA']);
        $this->assertSame('A', $perJabatan['KETUA'][0]->nama);

        $perPokja = StrukturPengurus::kelompokkanPerPokja($rows->where('unit', StrukturPengurus::UNIT_POKJA));
        $this->assertCount(1, $perPokja[$pokjaI->id]);
    }

    public function test_cetak_struktur_pkk_memuat_jabatan_inti_dan_pokja(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();
        $admin = $this->actingAdmin();

        foreach (['PEMBINA', 'KETUA', 'SEKRETARIS', 'BENDAHARA'] as $jabatan) {
            StrukturPengurus::query()->create([
                'kelurahan_id' => $kelurahan->id,
                'unit' => StrukturPengurus::UNIT_TP_PKK,
                'jabatan' => $jabatan,
                'nama' => 'Nama '.$jabatan,
                'urutan' => 0,
            ]);
        }

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_POKJA,
            'pokja_id' => $pokjaI->id,
            'jabatan' => 'Ketua Pokja',
            'nama' => 'AGUSTINAH',
            'urutan' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('cetak.show', [
            'buku' => 'struktur_pkk',
            'tahun' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('PEMBINA', false);
        $response->assertSee('KETUA', false);
        $response->assertSee('SEKRETARIS', false);
        $response->assertSee('BENDAHARA', false);
        $response->assertSee('Pokja I', false);
        $response->assertSee('AGUSTINAH', false);
    }

    public function test_cetak_struktur_lbs_memuat_seksi(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $admin = $this->actingAdmin();

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_LBS,
            'rt' => '49',
            'jabatan' => 'SEKSI I (Lingkungan Rumah Sehat)',
            'nama' => 'Contoh Seksi I',
            'urutan' => 0,
        ]);
        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_LBS,
            'rt' => '49',
            'jabatan' => 'SEKSI II (Indikator Lingkungan Hidup)',
            'nama' => 'Contoh Seksi II',
            'urutan' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('cetak.show', [
            'buku' => 'struktur_lbs',
            'tahun' => 2026,
            'rt' => '49',
        ]));

        $response->assertOk();
        $response->assertSee('SEKSI I', false);
        $response->assertSee('SEKSI II', false);
    }

    public function test_export_excel_urutan_kolom_struktur(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $admin = $this->actingAdmin();

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'jabatan' => 'KETUA',
            'nama' => 'HAIRANI',
            'urutan' => 0,
        ]);

        $responsePkk = $this->actingAs($admin)->get(route('export.buku', [
            'buku' => 'struktur_pkk',
            'tahun' => 2026,
        ]));
        $responsePkk->assertOk();
        $filePkk = $responsePkk->baseResponse->getFile();
        $this->assertNotNull($filePkk);
        $sheetPkk = IOFactory::load($filePkk->getPathname())->getActiveSheet();
        $this->assertSame('UNIT / BAGIAN', $sheetPkk->getCell('A1')->getValue());
        $this->assertSame('JABATAN', $sheetPkk->getCell('B1')->getValue());
        $this->assertSame('NAMA', $sheetPkk->getCell('C1')->getValue());

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_LBS,
            'rt' => '49',
            'jabatan' => 'KETUA',
            'nama' => 'RT 49',
            'urutan' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('export.buku', [
            'buku' => 'struktur_lbs',
            'tahun' => 2026,
            'rt' => '49',
        ]));
        $response->assertOk();
        $file = $response->baseResponse->getFile();
        $sheet = IOFactory::load($file->getPathname())->getActiveSheet();
        $this->assertSame('RT', $sheet->getCell('A1')->getValue());
        $this->assertSame('JABATAN', $sheet->getCell('B1')->getValue());
    }

    public function test_superadmin_bisa_pindah_kelurahan_dan_data_ikut(): void
    {
        ['kelurahan' => $gsi] = $this->seedMaster();
        $lain = Kelurahan::query()->create([
            'kode' => 'LAIN',
            'nama' => 'Kelurahan Lain Uji',
            'kecamatan' => 'Kec',
            'kota' => 'Kota',
            'provinsi' => 'Prov',
            'is_active' => false,
        ]);

        Orang::factory()->forKelurahan($gsi)->create(['nama' => 'Orang GSI']);
        Orang::factory()->forKelurahan($lain)->create(['nama' => 'Orang Lain']);

        $admin = $this->actingAdmin();

        $this->actingAs($admin)->get(route('orang.index'))->assertOk()->assertSee('Orang GSI', false);

        $this->actingAs($admin)->post(route('kelurahan-aktif.update'), [
            'kelurahan_id' => $lain->id,
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('orang.index'))->assertOk()->assertSee('Orang Lain', false)->assertDontSee('Orang GSI', false);
    }

    public function test_pengguna_biasa_tidak_bisa_memaksa_pindah_kelurahan(): void
    {
        ['kelurahan' => $gsi] = $this->seedMaster();
        $lain = Kelurahan::query()->create([
            'kode' => 'LAIN2',
            'nama' => 'Kelurahan Lain Dua',
            'kecamatan' => 'Kec',
            'kota' => 'Kota',
            'provinsi' => 'Prov',
            'is_active' => false,
        ]);

        Orang::factory()->forKelurahan($gsi)->create(['nama' => 'Tetap GSI']);
        Orang::factory()->forKelurahan($lain)->create(['nama' => 'Hanya Lain']);

        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-multi@pkk.test']);

        $this->actingAs($sekretaris)->post(route('kelurahan-aktif.update'), [
            'kelurahan_id' => $lain->id,
        ])->assertForbidden();

        session([ActiveKelurahan::SESSION_KEY => $lain->id]);

        $this->actingAs($sekretaris)->get(route('orang.index'))->assertOk()->assertSee('Tetap GSI', false)->assertDontSee('Hanya Lain', false);
    }

    public function test_dashboard_superadmin_memuat_nama_kelurahan_lain(): void
    {
        $this->seedMaster();
        Kelurahan::query()->create([
            'kode' => 'DASH',
            'nama' => 'Kelurahan Dashboard Uji',
            'kecamatan' => 'Kec',
            'kota' => 'Kota',
            'provinsi' => 'Prov',
            'is_active' => false,
        ]);

        $admin = $this->actingAdmin();
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Kelurahan Dashboard Uji', false);
    }

    public function test_tamu_dialihkan_ke_login_struktur(): void
    {
        $this->get(route('struktur.index'))->assertRedirect(route('login'));
    }
}
