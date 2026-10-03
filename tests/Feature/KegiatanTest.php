<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Notulen;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\Presensi;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KegiatanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin-kegiatan@pkk.test',
            'password' => 'password',
        ]);
    }

    /**
     * @return array{kelurahan: Kelurahan, pokja: \Illuminate\Support\Collection<int, Pokja>}
     */
    private function seedMaster(): array
    {
        $this->seed(MasterSeeder::class);
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokja = Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get();

        return ['kelurahan' => $kelurahan, 'pokja' => $pokja];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validKegiatanPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Rapat Koordinasi PKK',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-04-15',
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'tempat' => 'Balai Kelurahan',
            'acara' => 'Koordinasi bulanan',
            'uraian' => 'Rapat rutin',
        ], $overrides);
    }

    private function buatKegiatan(Kelurahan $kelurahan, array $attrs = []): Kegiatan
    {
        return Kegiatan::query()->create(array_merge([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Uji',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-05-01',
            'tempat' => 'Aula',
            'acara' => 'Acara uji',
        ], $attrs));
    }

    public function test_urut_presensi_berurutan_mulai_satu_setelah_simpan_daftar(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $o1 = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Peserta Satu']);
        $o2 = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Peserta Dua']);

        $this->actingAs($user)->post(route('kegiatan.presensi.store', $kegiatan), [
            'peserta' => [
                ['orang_id' => $o2->id, 'hadir' => '1'],
                ['orang_id' => $o1->id, 'hadir' => '1'],
                ['nama_manual' => 'Tamu Manual', 'hadir' => '1'],
            ],
        ])->assertRedirect(route('kegiatan.show', $kegiatan));

        $urut = Presensi::query()->where('kegiatan_id', $kegiatan->id)->orderBy('urut')->pluck('urut')->all();
        $this->assertSame([1, 2, 3], $urut);
    }

    public function test_peserta_manual_tanpa_orang_id_tetap_tersimpan_dan_nama_tampil(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.presensi.store', $kegiatan), [
            'peserta' => [
                ['nama_manual' => 'Ibu Tamu Undangan', 'alamat_manual' => 'Jl. Merdeka', 'hadir' => '1'],
            ],
        ]);

        $presensi = Presensi::query()->where('kegiatan_id', $kegiatan->id)->first();
        $this->assertNotNull($presensi);
        $this->assertNull($presensi->orang_id);
        $this->assertSame('Ibu Tamu Undangan', $presensi->nama_manual);
        $presensi->load('orang');
        $this->assertSame('Ibu Tamu Undangan', $presensi->nama_tampil);
    }

    public function test_peserta_hadir_bernilai_salah_tidak_hilang(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $orang = Orang::factory()->forKelurahan($kelurahan)->create();

        $this->actingAs($user)->post(route('kegiatan.presensi.store', $kegiatan), [
            'peserta' => [
                ['orang_id' => $orang->id, 'hadir' => '0', 'keterangan' => 'Izin'],
            ],
        ]);

        $this->assertDatabaseCount('presensi', 1);
        $this->assertDatabaseHas('presensi', [
            'kegiatan_id' => $kegiatan->id,
            'orang_id' => $orang->id,
            'hadir' => false,
            'keterangan' => 'Izin',
        ]);
    }

    public function test_satu_kegiatan_hanya_satu_notulen_simpan_dua_kali_memperbarui(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->actingAs($user)->post(route('kegiatan.notulen.store', $kegiatan), [
            'macam_rapat' => 'Rapat Pertama',
            'uraian_jalannya' => 'Pembukaan',
        ]);
        $this->actingAs($user)->post(route('kegiatan.notulen.store', $kegiatan), [
            'macam_rapat' => 'Rapat Diperbarui',
            'keputusan' => 'Disepakati',
        ]);

        $this->assertDatabaseCount('notulen', 1);
        $notulen = Notulen::query()->where('kegiatan_id', $kegiatan->id)->first();
        $this->assertNotNull($notulen);
        $this->assertSame('Rapat Diperbarui', $notulen->macam_rapat);
        $this->assertSame('Disepakati', $notulen->keputusan);
        $this->assertSame($user->id, $notulen->pembuat_id);
    }

    public function test_jumlah_hadir_pada_notulen_mengikuti_presensi_bila_kosong(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $o1 = Orang::factory()->forKelurahan($kelurahan)->create();
        $o2 = Orang::factory()->forKelurahan($kelurahan)->create();

        Presensi::factory()->untukKegiatan($kegiatan, 1)->untukOrang($o1)->create(['hadir' => true]);
        Presensi::factory()->untukKegiatan($kegiatan, 2)->untukOrang($o2)->create(['hadir' => false]);

        $this->actingAs($user)->post(route('kegiatan.notulen.store', $kegiatan), [
            'macam_rapat' => 'Rapat',
        ]);

        $notulen = Notulen::query()->where('kegiatan_id', $kegiatan->id)->first();
        $this->assertNotNull($notulen);
        $this->assertSame(1, $notulen->jumlah_hadir);
        $this->assertSame(1, $notulen->jumlah_tidak_hadir);
        $this->assertSame(1, $notulen->jumlah_hadir_aktual);
    }

    public function test_accessor_nama_tampil_untuk_orang_dan_manual(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $orang = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Nama Master']);

        $dariMaster = Presensi::factory()->untukKegiatan($kegiatan, 1)->untukOrang($orang)->create();
        $dariMaster->load('orang');
        $this->assertSame('Nama Master', $dariMaster->nama_tampil);

        $manual = Presensi::factory()->untukKegiatan($kegiatan, 2)->create([
            'orang_id' => null,
            'nama_manual' => 'Nama Manual Saja',
        ]);
        $this->assertSame('Nama Manual Saja', $manual->nama_tampil);
    }

    public function test_validasi_jam_selesai_lebih_awal_dari_jam_mulai_ditolak(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)
            ->post(route('kegiatan.store'), $this->validKegiatanPayload([
                'jam_mulai' => '14:00',
                'jam_selesai' => '10:00',
            ]))
            ->assertSessionHasErrors('jam_selesai');
    }

    public function test_tamu_dialihkan_ke_login_untuk_semua_rute_kegiatan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);

        $this->get(route('kegiatan.index'))->assertRedirect(route('login'));
        $this->get(route('kegiatan.create'))->assertRedirect(route('login'));
        $this->get(route('kegiatan.show', $kegiatan))->assertRedirect(route('login'));
        $this->get(route('kegiatan.edit', $kegiatan))->assertRedirect(route('login'));
    }

    public function test_halaman_create_show_edit_mengembalikan_200_untuk_pengguna_terautentikasi(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan, ['nama' => 'Kegiatan Tampil UI']);

        $this->actingAs($user)->get(route('kegiatan.create'))->assertOk()->assertSee('Tambah Kegiatan', false);
        $this->actingAs($user)->get(route('kegiatan.show', $kegiatan))->assertOk()->assertSee('Kegiatan Tampil UI', false);
        $this->actingAs($user)->get(route('kegiatan.edit', $kegiatan))->assertOk()->assertSee('Ubah Kegiatan', false);
    }

    public function test_store_kegiatan_baru_mengalihkan_ke_show(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $response = $this->actingAs($user)->post(route('kegiatan.store'), $this->validKegiatanPayload([
            'nama' => 'Kegiatan Baru E2E',
        ]));

        $kegiatan = Kegiatan::query()->where('nama', 'Kegiatan Baru E2E')->first();
        $this->assertNotNull($kegiatan);
        $response->assertRedirect(route('kegiatan.show', $kegiatan));
    }

    public function test_ubah_presensi_satuan_tidak_mengubah_urut(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $presensi = Presensi::factory()->untukKegiatan($kegiatan, 5)->create(['hadir' => true]);

        $this->actingAs($user)->patch(route('kegiatan.presensi.update', [$kegiatan, $presensi]), [
            'hadir' => '0',
            'keterangan' => 'Sakit',
        ])->assertRedirect(route('kegiatan.show', $kegiatan));

        $fresh = $presensi->fresh();
        $this->assertSame(5, $fresh->urut);
        $this->assertFalse($fresh->hadir);
        $this->assertSame('Sakit', $fresh->keterangan);
    }

    public function test_hadir_count_pada_model_kegiatan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        Presensi::factory()->untukKegiatan($kegiatan, 1)->create(['hadir' => true]);
        Presensi::factory()->untukKegiatan($kegiatan, 2)->tidakHadir()->create();

        $this->assertSame(1, $kegiatan->hadirCount());
    }
}
