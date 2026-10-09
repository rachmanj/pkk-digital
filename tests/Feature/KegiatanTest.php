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
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class KegiatanTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_presensi_tanpa_field_hadir_dianggap_tidak_hadir(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kegiatan = $this->buatKegiatan($kelurahan);
        $hadir = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Hadir Eksplisit']);
        $tanpaField = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Tanpa Field Hadir']);

        $this->actingAs($user)->post(route('kegiatan.presensi.store', $kegiatan), [
            'peserta' => [
                ['orang_id' => $hadir->id, 'hadir' => '1'],
                ['orang_id' => $tanpaField->id],
            ],
        ])->assertRedirect(route('kegiatan.show', $kegiatan));

        $this->assertDatabaseHas('presensi', [
            'kegiatan_id' => $kegiatan->id,
            'orang_id' => $hadir->id,
            'hadir' => true,
        ]);
        $this->assertDatabaseHas('presensi', [
            'kegiatan_id' => $kegiatan->id,
            'orang_id' => $tanpaField->id,
            'hadir' => false,
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

    public function test_store_unit_ketua_mengisi_pelaksana_dan_mengosongkan_pokja(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaI = $pokjaList->first();

        $this->actingAs($user)->post(route('kegiatan.store'), $this->validKegiatanPayload([
            'nama' => 'Rapat Ketua',
            'unit' => 'ketua',
            'pokja_id' => $pokjaI->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Rapat Ketua',
            'pelaksana' => Kegiatan::PELAKSANA_KETUA,
            'pokja_id' => null,
        ]);
    }

    public function test_store_unit_sekretaris_mengisi_pelaksana_dan_mengosongkan_pokja(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)->post(route('kegiatan.store'), $this->validKegiatanPayload([
            'nama' => 'Rapat Sekretaris',
            'unit' => 'sekretaris',
        ]))->assertRedirect();

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Rapat Sekretaris',
            'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS,
            'pokja_id' => null,
        ]);
    }

    public function test_store_unit_pokja_mengisi_pokja_id_dan_mengosongkan_pelaksana(): void
    {
        $user = $this->actingAdmin();
        ['pokja' => $pokjaList] = $this->seedMaster();
        $pokja = $pokjaList->first();

        $this->actingAs($user)->post(route('kegiatan.store'), $this->validKegiatanPayload([
            'nama' => 'Kegiatan Pokja',
            'unit' => 'pokja-'.$pokja->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Kegiatan Pokja',
            'pokja_id' => $pokja->id,
            'pelaksana' => null,
        ]);
    }

    public function test_store_unit_umum_kelurahan_tanpa_pokja_dan_pelaksana(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)->post(route('kegiatan.store'), $this->validKegiatanPayload([
            'nama' => 'Kegiatan Umum Kelurahan',
            'unit' => '',
        ]))->assertRedirect();

        $this->assertDatabaseHas('kegiatan', [
            'nama' => 'Kegiatan Umum Kelurahan',
            'pokja_id' => null,
            'pelaksana' => null,
        ]);
    }

    public function test_index_filter_unit_ketua_hanya_menampilkan_kegiatan_ketua(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokja = $pokjaList->first();

        $this->buatKegiatan($kelurahan, ['nama' => 'Hanya Ketua', 'pelaksana' => Kegiatan::PELAKSANA_KETUA, 'tanggal' => '2026-06-10']);
        $this->buatKegiatan($kelurahan, ['nama' => 'Bukan Ketua', 'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS, 'tanggal' => '2026-06-11']);
        $this->buatKegiatan($kelurahan, ['nama' => 'Pokja Saja', 'pokja_id' => $pokja->id, 'tanggal' => '2026-06-12']);

        $response = $this->actingAs($user)->get(route('kegiatan.index', [
            'tahun' => 2026,
            'unit' => 'ketua',
        ]));

        $response->assertOk();
        $response->assertSee('Hanya Ketua', false);
        $response->assertDontSee('Bukan Ketua', false);
        $response->assertDontSee('Pokja Saja', false);
    }

    public function test_index_filter_unit_sekretaris_hanya_menampilkan_kegiatan_sekretaris(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        $this->buatKegiatan($kelurahan, ['nama' => 'Hanya Sekretaris', 'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS, 'tanggal' => '2026-06-10']);
        $this->buatKegiatan($kelurahan, ['nama' => 'Bukan Sekretaris', 'pelaksana' => Kegiatan::PELAKSANA_KETUA, 'tanggal' => '2026-06-11']);

        $response = $this->actingAs($user)->get(route('kegiatan.index', [
            'tahun' => 2026,
            'unit' => 'sekretaris',
        ]));

        $response->assertOk();
        $response->assertSee('Hanya Sekretaris', false);
        $response->assertDontSee('Bukan Sekretaris', false);
    }

    public function test_index_filter_unit_pokja_hanya_menampilkan_pokja_tersebut(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaI = $pokjaList->first();
        $pokjaIi = $pokjaList->skip(1)->first();

        $this->buatKegiatan($kelurahan, ['nama' => 'Kegiatan Pokja I', 'pokja_id' => $pokjaI->id, 'tanggal' => '2026-06-10']);
        $this->buatKegiatan($kelurahan, ['nama' => 'Kegiatan Pokja II', 'pokja_id' => $pokjaIi->id, 'tanggal' => '2026-06-11']);

        $response = $this->actingAs($user)->get(route('kegiatan.index', [
            'tahun' => 2026,
            'unit' => 'pokja-'.$pokjaI->id,
        ]));

        $response->assertOk();
        $response->assertSee('Kegiatan Pokja I', false);
        $response->assertDontSee('Kegiatan Pokja II', false);
    }

    public function test_index_filter_lama_pokja_id_masih_bekerja(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaI = $pokjaList->first();
        $pokjaIi = $pokjaList->skip(1)->first();

        $this->buatKegiatan($kelurahan, ['nama' => 'Legacy Filter I', 'pokja_id' => $pokjaI->id, 'tanggal' => '2026-06-10']);
        $this->buatKegiatan($kelurahan, ['nama' => 'Legacy Filter II', 'pokja_id' => $pokjaIi->id, 'tanggal' => '2026-06-11']);

        $response = $this->actingAs($user)->get(route('kegiatan.index', [
            'tahun' => 2026,
            'pokja_id' => $pokjaI->id,
        ]));

        $response->assertOk();
        $response->assertSee('Legacy Filter I', false);
        $response->assertDontSee('Legacy Filter II', false);
    }

    public function test_cetak_buku_kegiatan_unit_ketua_judul_dan_baris_terfilter(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        $this->buatKegiatan($kelurahan, [
            'nama' => 'Kegiatan Cetak Ketua',
            'pelaksana' => Kegiatan::PELAKSANA_KETUA,
            'tanggal' => '2026-06-15',
        ]);
        $this->buatKegiatan($kelurahan, [
            'nama' => 'Kegiatan Cetak Lain',
            'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS,
            'tanggal' => '2026-06-16',
        ]);

        $response = $this->actingAs($user)->get(route('cetak.show', [
            'buku' => 'buku_kegiatan',
            'tahun' => 2026,
            'unit' => 'ketua',
        ]));

        $response->assertOk();
        $response->assertSee('Buku Kegiatan Ketua', false);
        $response->assertSee('Kegiatan Cetak Ketua', false);
        $response->assertDontSee('Kegiatan Cetak Lain', false);
    }

    public function test_export_excel_buku_kegiatan_mengikuti_filter_unit(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        $this->buatKegiatan($kelurahan, [
            'nama' => 'Excel Ketua Saja',
            'pelaksana' => Kegiatan::PELAKSANA_KETUA,
            'tanggal' => '2026-07-01',
        ]);
        $this->buatKegiatan($kelurahan, [
            'nama' => 'Excel Bukan Ketua',
            'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS,
            'tanggal' => '2026-07-02',
        ]);

        $response = $this->actingAs($user)->get(route('export.buku', [
            'buku' => 'buku_kegiatan',
            'tahun' => 2026,
            'unit' => 'ketua',
        ]));

        $response->assertOk();

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);
        $sheet = IOFactory::load($file->getPathname())->getActiveSheet();
        $foundKetua = false;
        $foundLain = false;
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $val = (string) $cell->getValue();
                if (str_contains($val, 'Excel Ketua Saja')) {
                    $foundKetua = true;
                }
                if (str_contains($val, 'Excel Bukan Ketua')) {
                    $foundLain = true;
                }
            }
        }
        $this->assertTrue($foundKetua, 'Baris kegiatan Ketua harus ada di export Excel.');
        $this->assertFalse($foundLain, 'Kegiatan di luar filter unit tidak boleh ada di export Excel.');
    }
}
