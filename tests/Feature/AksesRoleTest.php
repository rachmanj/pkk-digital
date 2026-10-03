<?php

namespace Tests\Feature;

use App\Models\AgendaSurat;
use App\Models\Keanggotaan;
use App\Models\Kelurahan;
use App\Models\Kegiatan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AksesRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kelurahan: Kelurahan, pokja: Pokja, pokjaIi: Pokja}
     */
    private function seedMaster(): array
    {
        $this->seed(MasterSeeder::class);
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokjaI = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'I')->firstOrFail();
        $pokjaIi = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'II')->firstOrFail();

        return ['kelurahan' => $kelurahan, 'pokja' => $pokjaI, 'pokjaIi' => $pokjaIi];
    }

    public function test_kader_dapat_menyimpan_presensi(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-test@pkk.test']);
        $kegiatan = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Presensi Kader',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-06-01',
            'tempat' => 'Aula',
            'acara' => 'Rapat',
        ]);

        $response = $this->actingAs($kader)->post(route('kegiatan.presensi.store', $kegiatan), [
            'peserta' => [
                ['nama_manual' => 'Tamu Rapat', 'hadir' => '1'],
            ],
        ]);

        $response->assertRedirect(route('kegiatan.show', $kegiatan));
        $this->assertDatabaseHas('presensi', [
            'kegiatan_id' => $kegiatan->id,
            'nama_manual' => 'Tamu Rapat',
            'hadir' => 1,
        ]);
    }

    public function test_kader_ditolak_halaman_pengguna_dan_hapus_orang(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-akses@pkk.test']);
        $orang = Orang::factory()->create(['kelurahan_id' => $kelurahan->id]);

        $this->actingAs($kader)->get(route('pengguna.index'))->assertForbidden();
        $this->actingAs($kader)->delete(route('orang.destroy', $orang))->assertForbidden();
    }

    public function test_sekretaris_dapat_membuat_surat_dan_tidak_bisa_audit(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sekretaris-test@pkk.test']);

        $this->actingAs($sekretaris)->get(route('agenda-surat.create'))->assertOk();

        $this->actingAs($sekretaris)->post(route('agenda-surat.store'), [
            'jenis' => AgendaSurat::JENIS_MASUK,
            'tanggal_surat' => '2026-04-01',
            'tanggal_terima' => '2026-04-02',
            'no_surat' => 'SEC/001/2026',
            'dari' => 'Dinas',
            'perihal' => 'Uji sekretaris',
        ])->assertRedirect();

        $this->assertDatabaseHas('agenda_surat', ['no_surat' => 'SEC/001/2026']);
        $this->actingAs($sekretaris)->get(route('audit.index'))->assertForbidden();
    }

    public function test_ketua_hanya_lihat_buku_dan_tidak_bisa_simpan_surat(): void
    {
        $this->seedMaster();
        $ketua = $this->userForRole('ketua', ['email' => 'ketua-test@pkk.test']);

        $this->actingAs($ketua)->get(route('agenda-surat.index'))->assertOk();
        $this->actingAs($ketua)->get(route('agenda-surat.create'))->assertForbidden();

        $this->actingAs($ketua)->post(route('agenda-surat.store'), [
            'jenis' => AgendaSurat::JENIS_MASUK,
            'tanggal_surat' => '2026-04-01',
            'tanggal_terima' => '2026-04-02',
            'no_surat' => 'KETUA/001',
            'dari' => 'X',
            'perihal' => 'Tidak boleh',
        ])->assertForbidden();
    }

    public function test_ketua_pokja_i_melihat_surat_pokja_i_dan_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaI, 'pokjaIi' => $pokjaIi] = $this->seedMaster();

        $suratKelurahan = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-01-01',
            'tanggal_terima' => '2026-01-02',
            'no_surat' => 'KEL/TEST',
            'dari' => 'Kelurahan',
            'perihal' => 'Tingkat kelurahan',
        ]);

        $suratI = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaI->id,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-05-01',
            'tanggal_terima' => '2026-05-02',
            'no_surat' => 'POKJA-I/1',
            'dari' => 'A',
            'perihal' => 'Pokja I',
        ]);

        $suratIi = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaIi->id,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-05-03',
            'tanggal_terima' => '2026-05-04',
            'no_surat' => 'POKJA-II/1',
            'dari' => 'B',
            'perihal' => 'Pokja II',
        ]);

        $ketuaPokja = $this->userForRole('ketua_pokja', [
            'email' => 'ketua-pokja-test@pkk.test',
            'pokja_id' => $pokjaI->id,
        ]);

        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratKelurahan))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratI))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratIi))->assertForbidden();
    }

    public function test_superadmin_dapat_membuka_halaman_pengguna_dan_audit(): void
    {
        $this->seedMaster();
        $admin = $this->actingAdmin();

        $this->actingAs($admin)->get(route('pengguna.index'))->assertOk();
        $this->actingAs($admin)->get(route('audit.index'))->assertOk();
    }

    public function test_role_permission_seeder_idempoten(): void
    {
        $this->seed(MasterSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(5, Role::query()->count());
        $this->assertSame(5, User::query()->where('email', 'like', '%@pkk.test')->count());
        $this->assertDatabaseHas('users', ['username' => 'kader', 'email' => 'kader@pkk.test']);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'email' => 'admin@pkk.test']);
    }

    public function test_update_orang_mencatat_activity_log_dengan_nama_log_orang(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $admin = $this->actingAdmin();
        $orang = Orang::factory()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Nama Awal',
        ]);
        Activity::query()->where('log_name', 'orang')->delete();

        $response = $this->actingAs($admin)->put(route('orang.update', $orang), [
            'nama' => 'Nama Diubah',
            'jenis_kelamin' => 'P',
            'keanggotaan' => [
                [
                    'jenis' => Keanggotaan::JENIS_KADER_UMUM,
                    'pokja_id' => null,
                    'jabatan' => 'Kader',
                    'is_aktif' => '1',
                ],
            ],
        ]);
        $response->assertRedirect(route('orang.show', $orang));
        $response->assertSessionHasNoErrors();

        $this->assertSame(1, Activity::query()->where('log_name', 'orang')->count());
        $activity = Activity::query()->where('log_name', 'orang')->first();
        $this->assertNotNull($activity);
        $this->assertSame('updated', $activity->description);
        $this->assertSame($admin->id, $activity->causer_id);
    }

    public function test_sidebar_kader_tidak_memuat_tautan_pengguna(): void
    {
        $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-menu@pkk.test']);

        $html = $this->actingAs($kader)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('pengguna.index'), $html);
        $this->assertStringNotContainsString('>Pengguna<', $html);
    }
}
