<?php

namespace Tests\Feature;

use App\Models\AgendaSurat;
use App\Models\Disposisi;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AgendaSuratTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin@pkk.test',
            'password' => 'password',
        ]);
    }

    /**
     * @return array{kelurahan: Kelurahan, pokja: Collection<int, Pokja>}
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
    private function validMasukPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'tanggal_surat' => '2026-03-01',
            'tanggal_terima' => '2026-03-02',
            'no_surat' => '001/DINAS/2026',
            'dari' => 'Dinas Kesehatan',
            'perihal' => 'Undangan rapat',
        ], $overrides);
    }

    public function test_nomor_urut_masuk_dan_keluar_terpisah_dan_berurutan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-01-10',
            'tanggal_terima' => '2026-01-11',
            'no_surat' => 'A/1',
            'dari' => 'A',
            'perihal' => 'Satu',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 2,
            'tanggal_surat' => '2026-02-10',
            'tanggal_terima' => '2026-02-11',
            'no_surat' => 'A/2',
            'dari' => 'B',
            'perihal' => 'Dua',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_KELUAR,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-01-15',
            'no_surat' => 'K/1',
            'kepada' => 'X',
            'perihal' => 'Keluar satu',
        ]);

        $this->assertSame(3, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            null,
            2026
        ));

        $this->assertSame(2, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_KELUAR,
            null,
            2026
        ));
    }

    public function test_nomor_urut_tahun_baru_dimulai_dari_satu(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 5,
            'tanggal_surat' => '2025-12-20',
            'tanggal_terima' => '2025-12-21',
            'no_surat' => 'OLD/1',
            'dari' => 'Z',
            'perihal' => 'Tahun lalu',
        ]);

        $this->assertSame(1, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            null,
            2026
        ));
    }

    public function test_satu_surat_bisa_punya_tiga_disposisi_ke_pokja_berbeda(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();

        $surat = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-04-01',
            'tanggal_terima' => '2026-04-02',
            'no_surat' => 'DIS/1',
            'dari' => 'Instansi',
            'perihal' => 'Disposisi',
        ]);

        foreach ($pokjaList->take(3) as $pokja) {
            Disposisi::query()->create([
                'agenda_surat_id' => $surat->id,
                'pokja_id' => $pokja->id,
                'status' => Disposisi::STATUS_BARU,
            ]);
        }

        $this->assertCount(3, $surat->fresh()->disposisi);
    }

    public function test_status_disposisi_berpindah_dan_selesai_at_terisi(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();

        $surat = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-05-01',
            'tanggal_terima' => '2026-05-02',
            'no_surat' => 'ST/1',
            'dari' => 'A',
            'perihal' => 'Alur status',
        ]);

        $disposisi = Disposisi::query()->create([
            'agenda_surat_id' => $surat->id,
            'pokja_id' => $pokjaList->first()->id,
            'status' => Disposisi::STATUS_BARU,
        ]);

        $this->actingAs($user)
            ->put(route('disposisi.update', $disposisi), [
                'status' => Disposisi::STATUS_PROSES,
                'instruksi' => 'Proses',
            ])
            ->assertRedirect(route('agenda-surat.show', $surat));

        $disposisi->refresh();
        $this->assertSame(Disposisi::STATUS_PROSES, $disposisi->status);

        $this->actingAs($user)
            ->put(route('disposisi.update', $disposisi), [
                'status' => Disposisi::STATUS_SELESAI,
                'instruksi' => 'Selesai',
            ])
            ->assertRedirect(route('agenda-surat.show', $surat));

        $disposisi->refresh();
        $this->assertSame(Disposisi::STATUS_SELESAI, $disposisi->status);
        $this->assertNotNull($disposisi->selesai_at);
        $this->assertSame($user->id, $disposisi->oleh_user_id);
    }

    public function test_accessor_terlambat_benar_untuk_tenggat_lampau(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();

        $surat = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-06-01',
            'tanggal_terima' => '2026-06-02',
            'no_surat' => 'TL/1',
            'dari' => 'A',
            'perihal' => 'Terlambat',
        ]);

        $disposisi = Disposisi::query()->create([
            'agenda_surat_id' => $surat->id,
            'pokja_id' => $pokjaList->first()->id,
            'status' => Disposisi::STATUS_PROSES,
            'tenggat' => now()->subDays(3)->toDateString(),
        ]);

        $this->assertTrue($disposisi->terlambat);

        $disposisi->update(['status' => Disposisi::STATUS_SELESAI]);
        $this->assertFalse($disposisi->fresh()->terlambat);
    }

    public function test_store_surat_masuk_tanpa_tanggal_terima_ditolak(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $payload = $this->validMasukPayload(['tanggal_terima' => '']);

        $this->actingAs($user)
            ->post(route('agenda-surat.store'), $payload)
            ->assertSessionHasErrors('tanggal_terima');
    }

    public function test_store_surat_keluar_tanpa_kepada_ditolak(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)
            ->post(route('agenda-surat.store'), [
                'jenis' => AgendaSurat::JENIS_KELUAR,
                'tanggal_surat' => '2026-07-01',
                'no_surat' => 'K/9',
                'perihal' => 'Tanpa kepada',
                'kepada' => '',
            ])
            ->assertSessionHasErrors('kepada');
    }

    public function test_tamu_dialihkan_ke_login_untuk_rute_agenda_surat(): void
    {
        $this->get(route('agenda-surat.index'))->assertRedirect(route('login'));
        $this->get(route('agenda-surat.create'))->assertRedirect(route('login'));
    }

    public function test_pengguna_terautentikasi_bisa_membuka_index_dan_show(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        $surat = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-08-01',
            'tanggal_terima' => '2026-08-02',
            'no_surat' => 'UI/1',
            'dari' => 'A',
            'perihal' => 'Tampil',
        ]);

        $this->actingAs($user)
            ->get(route('agenda-surat.index'))
            ->assertOk()
            ->assertSee('Agenda Surat');

        $this->actingAs($user)
            ->get(route('agenda-surat.show', $surat))
            ->assertOk()
            ->assertSee('UI/1');
    }

    public function test_store_surat_masuk_tahun_berbeda_pada_buku_pokja_sama_keduanya_nomor_satu(): void
    {
        $user = $this->actingAdmin();
        ['pokja' => $pokjaList] = $this->seedMaster();
        $pokjaId = $pokjaList->first()->id;

        $payload2026 = $this->validMasukPayload([
            'pokja_id' => $pokjaId,
            'tanggal_surat' => '2026-03-01',
            'tanggal_terima' => '2026-03-02',
            'no_surat' => 'MASUK/2026/1',
        ]);

        $response2026 = $this->actingAs($user)
            ->post(route('agenda-surat.store'), $payload2026);

        $response2026->assertRedirect();
        $surat2026 = AgendaSurat::query()->where('no_surat', 'MASUK/2026/1')->first();
        $this->assertNotNull($surat2026);
        $this->assertSame(1, $surat2026->no_urut_tahun);
        $this->assertSame(2026, $surat2026->tahun);

        $payload2027 = $this->validMasukPayload([
            'pokja_id' => $pokjaId,
            'tanggal_surat' => '2027-03-01',
            'tanggal_terima' => '2027-03-02',
            'no_surat' => 'MASUK/2027/1',
        ]);

        $response2027 = $this->actingAs($user)
            ->post(route('agenda-surat.store'), $payload2027);

        $response2027->assertRedirect();
        $surat2027 = AgendaSurat::query()->where('no_surat', 'MASUK/2027/1')->first();
        $this->assertNotNull($surat2027);
        $this->assertSame(1, $surat2027->no_urut_tahun);
        $this->assertSame(2027, $surat2027->tahun);
        $this->assertSame($surat2026->pokja_id, $surat2027->pokja_id);

        $this->assertSame(2, AgendaSurat::query()->where('pokja_id', $pokjaId)->count());
    }

    public function test_nomor_urut_setelah_tahun_baru_kembali_mengikuti_data_tahun_asal(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaId = $pokjaList->first()->id;

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaId,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-06-01',
            'tanggal_terima' => '2026-06-02',
            'no_surat' => 'A/2026',
            'dari' => 'X',
            'perihal' => 'Tahun 2026',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaId,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2027-01-15',
            'tanggal_terima' => '2027-01-16',
            'no_surat' => 'A/2027',
            'dari' => 'Y',
            'perihal' => 'Tahun 2027',
        ]);

        $this->assertSame(2, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            $pokjaId,
            2026
        ));

        $this->assertSame(2, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            $pokjaId,
            2027
        ));
    }

    public function test_nomor_urut_masuk_dan_keluar_terpisah_per_tahun(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaId = $pokjaList->first()->id;

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaId,
            'no_urut_tahun' => 3,
            'tanggal_surat' => '2026-04-01',
            'tanggal_terima' => '2026-04-02',
            'no_surat' => 'M/3',
            'dari' => 'A',
            'perihal' => 'Masuk',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_KELUAR,
            'pokja_id' => $pokjaId,
            'no_urut_tahun' => 2,
            'tanggal_surat' => '2026-04-10',
            'no_surat' => 'K/2',
            'kepada' => 'B',
            'perihal' => 'Keluar',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaId,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2027-04-01',
            'tanggal_terima' => '2027-04-02',
            'no_surat' => 'M/2027',
            'dari' => 'C',
            'perihal' => 'Masuk tahun baru',
        ]);

        $this->assertSame(4, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            $pokjaId,
            2026
        ));

        $this->assertSame(3, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_KELUAR,
            $pokjaId,
            2026
        ));

        $this->assertSame(2, AgendaSurat::nomorUrutBerikutnya(
            $kelurahan->id,
            AgendaSurat::JENIS_MASUK,
            $pokjaId,
            2027
        ));
    }

    public function test_scope_tahun_hanya_mengembalikan_baris_tahun_tersebut(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2026-01-01',
            'tanggal_terima' => '2026-01-02',
            'no_surat' => 'SCOPE/2026',
            'dari' => 'A',
            'perihal' => '2026',
        ]);

        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tanggal_surat' => '2027-01-01',
            'tanggal_terima' => '2027-01-02',
            'no_surat' => 'SCOPE/2027',
            'dari' => 'B',
            'perihal' => '2027',
        ]);

        $ids2026 = AgendaSurat::query()->tahun(2026)->pluck('no_surat')->all();
        $ids2027 = AgendaSurat::query()->tahun(2027)->pluck('no_surat')->all();

        $this->assertSame(['SCOPE/2026'], $ids2026);
        $this->assertSame(['SCOPE/2027'], $ids2027);
    }
}
