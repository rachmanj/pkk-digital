<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\KegiatanFoto;
use App\Models\Kelurahan;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KegiatanFotoTest extends TestCase
{
    use RefreshDatabase;

    private function seedMaster(): Kelurahan
    {
        $this->seed(MasterSeeder::class);

        return Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
    }

    private function buatKegiatan(Kelurahan $kelurahan, array $attrs = []): Kegiatan
    {
        return Kegiatan::query()->create(array_merge([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Uji Foto',
            'jenis' => Kegiatan::JENIS_PEMBINAAN,
            'tanggal' => '2026-05-01',
            'tempat' => 'Aula',
            'acara' => 'Acara uji',
        ], $attrs));
    }

    /**
     * @return list<UploadedFile>
     */
    private function gambarPalsu(int $jumlah = 1): array
    {
        $files = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $files[] = UploadedFile::fake()->image('foto-'.$i.'.jpg', 80, 80);
        }

        return $files;
    }

    public function test_unggah_dua_foto_sekaligus_menyimpan_dua_baris_dan_berkas_di_disk(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(2),
            'keterangan' => 'Dokumentasi pembinaan',
        ])->assertRedirect(route('kegiatan.show', $kegiatan));

        $this->assertDatabaseCount('kegiatan_foto', 2);
        $foto = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->orderBy('urut')->get();
        $this->assertSame([1, 2], $foto->pluck('urut')->all());
        $this->assertSame('Dokumentasi pembinaan', $foto->first()->keterangan);
        $this->assertSame($user->id, $foto->first()->uploaded_by);

        foreach ($foto as $baris) {
            Storage::disk('local')->assertExists($baris->file_path);
        }
    }

    public function test_urut_melanjutkan_nomor_setelah_unggahan_kedua(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(2),
        ]);
        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(2),
        ]);

        $urut = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->orderBy('urut')->pluck('urut')->all();
        $this->assertSame([1, 2, 3, 4], $urut);
    }

    public function test_berkas_non_gambar_ditolak(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => [UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf')],
        ])->assertSessionHasErrors('foto.0');

        $this->assertDatabaseCount('kegiatan_foto', 0);
    }

    public function test_berkas_lebih_dari_4_mb_ditolak(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => [UploadedFile::fake()->create('besar.jpg', 4097, 'image/jpeg')],
        ])->assertSessionHasErrors('foto.0');

        $this->assertDatabaseCount('kegiatan_foto', 0);
    }

    public function test_lebih_dari_12_berkas_sekaligus_ditolak(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(13),
        ])->assertSessionHasErrors('foto');

        $this->assertDatabaseCount('kegiatan_foto', 0);
    }

    public function test_pengguna_tanpa_izin_unggah_mendapat_403(): void
    {
        Storage::fake('local');
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $ketua = $this->userForRole('ketua', ['username' => 'ketua-foto', 'email' => 'ketua-foto@pkk.test']);

        $this->actingAs($ketua)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(1),
        ])->assertForbidden();

        $this->assertDatabaseCount('kegiatan_foto', 0);
    }

    public function test_kader_boleh_mengunggah_tetapi_403_saat_menghapus(): void
    {
        Storage::fake('local');
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $kader = $this->userForRole('kader', ['username' => 'kader-foto', 'email' => 'kader-foto@pkk.test']);

        $this->actingAs($kader)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(1),
        ])->assertRedirect(route('kegiatan.show', $kegiatan));

        $foto = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->first();
        $this->assertNotNull($foto);

        $this->actingAs($kader)->delete(route('kegiatan-foto.destroy', $foto))->assertForbidden();
        $this->assertDatabaseHas('kegiatan_foto', ['id' => $foto->id]);
    }

    public function test_pemilik_kelola_kegiatan_bisa_menghapus_dan_berkas_fisik_ikut_terhapus(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(1),
        ]);

        $foto = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->firstOrFail();
        $path = $foto->file_path;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user)->delete(route('kegiatan-foto.destroy', $foto))
            ->assertRedirect(route('kegiatan.show', $kegiatan));

        $this->assertDatabaseMissing('kegiatan_foto', ['id' => $foto->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_rute_berkas_mengembalikan_gambar_untuk_pengguna_terautentikasi(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(1),
        ]);

        $foto = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->firstOrFail();

        $response = $this->actingAs($user)->get(route('kegiatan-foto.berkas', $foto));
        $response->assertOk();
        $this->assertStringContainsString('image/', (string) $response->headers->get('content-type'));
    }

    public function test_tamu_dialihkan_ke_login_saat_mengakses_berkas_foto(): void
    {
        Storage::fake('local');
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $admin = $this->actingAdmin();

        $this->actingAs($admin)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(1),
        ]);

        $foto = KegiatanFoto::query()->where('kegiatan_id', $kegiatan->id)->firstOrFail();

        auth()->logout();
        $this->get(route('kegiatan-foto.berkas', $foto))->assertRedirect(route('login'));
    }

    public function test_halaman_kegiatan_show_memuat_panel_foto_dan_jumlah_foto(): void
    {
        Storage::fake('local');
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.foto.store', $kegiatan), [
            'foto' => $this->gambarPalsu(2),
        ]);

        $this->actingAs($user)->get(route('kegiatan.show', $kegiatan))
            ->assertOk()
            ->assertSee('Foto Kegiatan', false)
            ->assertSee('2 foto', false);
    }
}
