<?php

namespace Tests\Feature;

use App\Exports\ProgramKerjaExport;
use App\Exports\ProgramKerjaMatriksExport;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\ProgramKerja;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProgramKerjaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kelurahan: Kelurahan, pokjaI: Pokja, pokjaIi: Pokja}
     */
    private function seedMaster(): array
    {
        $this->seed(MasterSeeder::class);
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokjaI = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'I')->firstOrFail();
        $pokjaIi = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'II')->firstOrFail();

        return ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI, 'pokjaIi' => $pokjaIi];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'pokja_id' => null,
            'tahun' => 2026,
            'kode' => 'A',
            'program' => 'Program utama',
            'kegiatan' => 'Pertemuan Pengurus PKK',
            'tanggal_kegiatan' => '2026-03-10',
            'tujuan' => 'Koordinasi',
            'sasaran' => 'Pengurus',
            'tempat' => 'Balai',
            'sumber_dana' => 'APBDes',
            'keterangan' => 'Catatan',
            'bulan_rencana' => [1, 3],
            'bulan_pelaksanaan' => [3],
        ], $overrides);
    }

    private function buatProgramKerja(Kelurahan $kelurahan, ?int $pokjaId, array $attrs = []): ProgramKerja
    {
        return ProgramKerja::query()->create(array_merge([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => 2026,
            'kode' => 'B',
            'program' => null,
            'kegiatan' => 'Jenis default',
            'tanggal_kegiatan' => null,
            'tujuan' => null,
            'sasaran' => null,
            'tempat' => null,
            'sumber_dana' => null,
            'keterangan' => null,
            'bulan_rencana' => [2],
            'bulan_pelaksanaan' => [4],
        ], $attrs));
    }

    public function test_halaman_crud_200_untuk_pengguna_berhak(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-pk@pkk.test']);

        $this->actingAs($sekretaris)->get(route('program-kerja.index'))->assertOk();
        $this->actingAs($sekretaris)->get(route('program-kerja.create'))->assertOk();

        $response = $this->actingAs($sekretaris)->post(route('program-kerja.store'), $this->validPayload());
        $response->assertRedirect();
        $item = ProgramKerja::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->first();
        $this->assertNotNull($item);

        $this->actingAs($sekretaris)->get(route('program-kerja.show', $item))->assertOk();
        $this->actingAs($sekretaris)->get(route('program-kerja.edit', $item))->assertOk();
    }

    public function test_kader_boleh_lihat_tapi_403_simpan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-pk@pkk.test']);
        $item = $this->buatProgramKerja($kelurahan, null);

        $this->actingAs($kader)->get(route('program-kerja.index'))->assertOk();
        $this->actingAs($kader)->post(route('program-kerja.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($kader)->delete(route('program-kerja.destroy', $item))->assertForbidden();
    }

    public function test_ketua_hanya_lihat_403_simpan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $ketua = $this->userForRole('ketua', ['email' => 'ketua-pk@pkk.test']);
        $item = $this->buatProgramKerja($kelurahan, null);

        $this->actingAs($ketua)->get(route('program-kerja.index'))->assertOk();
        $this->actingAs($ketua)->post(route('program-kerja.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($ketua)->delete(route('program-kerja.destroy', $item))->assertForbidden();
    }

    public function test_ketua_pokja_i_boleh_simpan_pokja_i_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI, 'pokjaIi' => $pokjaIi] = $this->seedMaster();
        $ketuaPokja = $this->userForRole('ketua_pokja', [
            'email' => 'kp-pk@pkk.test',
            'pokja_id' => $pokjaI->id,
        ]);

        $this->actingAs($ketuaPokja)->post(route('program-kerja.store'), $this->validPayload([
            'pokja_id' => $pokjaI->id,
            'kegiatan' => 'Kegiatan Pokja I',
        ]))->assertRedirect();

        $this->assertTrue(ProgramKerja::query()->where('pokja_id', $pokjaI->id)->where('kegiatan', 'Kegiatan Pokja I')->exists());

        $this->actingAs($ketuaPokja)->post(route('program-kerja.store'), $this->validPayload([
            'pokja_id' => $pokjaIi->id,
            'kegiatan' => 'Kegiatan Pokja II',
        ]))->assertForbidden();

        $barangIi = $this->buatProgramKerja($kelurahan, $pokjaIi->id, ['kegiatan' => 'Miliki Pokja II']);
        $this->actingAs($ketuaPokja)->get(route('program-kerja.index', ['buku' => 'pokja-'.$pokjaIi->id]))->assertForbidden();
        $this->actingAs($ketuaPokja)->get(route('program-kerja.show', $barangIi))->assertForbidden();
    }

    public function test_validasi_kegiatan_dan_tahun_wajib(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-valid-pk@pkk.test']);

        $this->actingAs($sekretaris)->post(route('program-kerja.store'), $this->validPayload(['kegiatan' => '']))
            ->assertSessionHasErrors('kegiatan');
        $this->actingAs($sekretaris)->post(route('program-kerja.store'), $this->validPayload(['tahun' => '']))
            ->assertSessionHasErrors('tahun');
    }

    public function test_simpan_dan_ubah_bulan_sebagai_array(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-bulan-pk@pkk.test']);

        $this->actingAs($sekretaris)->post(route('program-kerja.store'), $this->validPayload([
            'bulan_rencana' => [5, 1, 5],
            'bulan_pelaksanaan' => [6, 12],
        ]))->assertRedirect();

        $item = ProgramKerja::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->firstOrFail();
        $this->assertSame([1, 5], $item->bulan_rencana);
        $this->assertSame([6, 12], $item->bulan_pelaksanaan);

        $this->actingAs($sekretaris)->put(route('program-kerja.update', $item), $this->validPayload([
            'bulan_rencana' => [2],
            'bulan_pelaksanaan' => [8],
            'kegiatan' => 'Diubah',
        ]))->assertRedirect();

        $item->refresh();
        $this->assertSame([2], $item->bulan_rencana);
        $this->assertSame([8], $item->bulan_pelaksanaan);
        $this->assertSame('Diubah', $item->kegiatan);
    }

    public function test_matriks_di_halaman_rincian_menampilkan_centang_bulan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-matriks-pk@pkk.test']);
        $item = $this->buatProgramKerja($kelurahan, null, [
            'bulan_rencana' => [2],
            'bulan_pelaksanaan' => [4],
        ]);

        $response = $this->actingAs($sekretaris)->get(route('program-kerja.show', $item));
        $response->assertOk();
        $response->assertSee(ProgramKerja::TANDA_CENTANG, false);
        $response->assertSee('title="Manual"', false);
    }

    public function test_bulan_pelaksanaan_otomatis_dari_kegiatan_tertaut_dengan_penanda(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-realisasi-pk@pkk.test']);
        $item = $this->buatProgramKerja($kelurahan, null, [
            'bulan_pelaksanaan' => [],
            'kegiatan' => 'Realisasi uji',
        ]);

        Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Realisasi',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-07-15',
            'tempat' => 'Aula',
            'acara' => 'Rapat',
            'program_kerja_id' => $item->id,
        ]);

        $response = $this->actingAs($sekretaris)->get(route('program-kerja.show', $item));
        $response->assertOk();
        $response->assertSee('Kegiatan Realisasi', false);
        $response->assertSee('1 kegiatan', false);
        $response->assertSee('title="Dari kegiatan tertaut"', false);
    }

    public function test_halaman_cetak_program_kerja_memuat_judul_kolom(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-cetak-pk@pkk.test']);
        $query = http_build_query(['tahun' => 2026, 'buku' => 'kelurahan']);

        $response = $this->actingAs($sekretaris)->get(route('cetak.show', ['buku' => 'program_kerja']).'?'.$query);
        $response->assertOk();
        $response->assertSee('TGL. KEGIATAN', false);
        $response->assertSee('SUMBER DANA', false);
        $response->assertSee('KET.', false);
    }

    public function test_halaman_cetak_matriks_memuat_bulan_perencanaan_dan_pelaksanaan(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-cetak-matriks@pkk.test']);
        $query = http_build_query(['tahun' => 2026, 'buku' => 'kelurahan']);

        $response = $this->actingAs($sekretaris)->get(route('cetak.show', ['buku' => 'program_kerja_matriks']).'?'.$query);
        $response->assertOk();
        $response->assertSee('BULAN PERENCANAAN', false);
        $response->assertSee('BULAN PELAKSANAAN', false);
        for ($i = 1; $i <= 12; $i++) {
            $response->assertSee('>'.$i.'<', false);
        }
    }

    public function test_export_excel_memuat_urutan_header_config(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-export-pk@pkk.test']);
        $this->buatProgramKerja($kelurahan, null, ['kegiatan' => 'Export Test']);

        Excel::fake();

        $query = http_build_query(['tahun' => 2026, 'buku' => 'kelurahan']);
        $this->actingAs($sekretaris)->get(route('export.buku', ['buku' => 'program_kerja']).'?'.$query)->assertOk();

        Excel::assertDownloaded('program_kerja-2026.xlsx', function (ProgramKerjaExport $export) {
            $expected = array_column(config('buku.program_kerja.kolom'), 'label');
            $this->assertSame($expected, $export->headings());

            return true;
        });

        $this->actingAs($sekretaris)->get(route('export.buku', ['buku' => 'program_kerja_matriks']).'?'.$query)->assertOk();

        Excel::assertDownloaded('program_kerja_matriks-2026.xlsx', function (ProgramKerjaMatriksExport $export) {
            $expected = array_column(config('buku.program_kerja_matriks.kolom'), 'label');
            $this->assertSame($expected, $export->headings());

            return true;
        });
    }

    public function test_menautkan_kegiatan_tidak_merusak_tes_kegiatan_dasar(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-link-pk@pkk.test']);

        $this->actingAs($sekretaris)->post(route('kegiatan.store'), [
            'nama' => 'Tanpa Program Kerja',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-04-15',
            'tempat' => 'Balai',
            'acara' => 'Rapat',
        ])->assertRedirect();

        $kegiatan = Kegiatan::query()->where('nama', 'Tanpa Program Kerja')->firstOrFail();
        $this->assertNull($kegiatan->program_kerja_id);
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->seedMaster();

        $this->get(route('program-kerja.index'))->assertRedirect(route('login'));
    }
}
