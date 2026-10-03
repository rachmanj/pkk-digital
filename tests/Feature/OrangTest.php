<?php

namespace Tests\Feature;

use App\Models\Keanggotaan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class OrangTest extends TestCase
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
     * @return array{kode: string, pokja: Collection<int, Pokja>}
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
    private function validOrangPayload(array $overrides = []): array
    {
        $base = [
            'nama' => 'Siti Aminah',
            'jenis_kelamin' => 'P',
            'keanggotaan' => [
                [
                    'jenis' => Keanggotaan::JENIS_TP_PKK,
                    'pokja_id' => null,
                    'jabatan' => 'Anggota',
                    'no_registrasi' => 'REG-0001',
                    'is_aktif' => '1',
                ],
            ],
        ];

        return array_replace_recursive($base, $overrides);
    }

    public function test_store_orang_tanpa_nama_ditolak(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();
        $pokja = Pokja::query()->firstOrFail();

        $payload = $this->validOrangPayload([
            'nama' => '',
            'keanggotaan' => [
                [
                    'jenis' => Keanggotaan::JENIS_TP_PKK,
                    'pokja_id' => $pokja->id,
                    'jabatan' => 'Ketua Pokja I',
                    'no_registrasi' => 'REG-0002',
                    'is_aktif' => '1',
                ],
            ],
        ]);
        unset($payload['nama']);

        $response = $this->actingAs($user)->post(route('orang.store'), $payload);

        $response->assertSessionHasErrors('nama');
    }

    public function test_jenis_kelamin_selain_l_atau_p_ditolak(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $payload = $this->validOrangPayload(['jenis_kelamin' => 'X']);

        $response = $this->actingAs($user)->post(route('orang.store'), $payload);

        $response->assertSessionHasErrors('jenis_kelamin');
    }

    public function test_satu_orang_bisa_punya_keanggotaan_di_dua_pokja_berbeda(): void
    {
        $master = $this->seedMaster();
        $pokjaI = $master['pokja']->firstWhere('kode', 'I');
        $pokjaII = $master['pokja']->firstWhere('kode', 'II');

        $orang = Orang::factory()->forKelurahan($master['kelurahan'])->create();

        Keanggotaan::factory()->forOrangKelurahan($orang)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaI->id,
            'jabatan' => 'Ketua Pokja I',
            'no_registrasi' => 'REG-A-001',
        ]);

        Keanggotaan::factory()->forOrangKelurahan($orang)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaII->id,
            'jabatan' => 'Sekretaris Pokja II',
            'no_registrasi' => 'REG-B-002',
        ]);

        $orang->refresh();

        $this->assertCount(2, $orang->keanggotaan);
        $this->assertSame(
            [$pokjaI->id, $pokjaII->id],
            $orang->keanggotaan->pluck('pokja_id')->sort()->values()->all()
        );
    }

    public function test_filter_pokja_mengembalikan_jumlah_baris_tepat(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();
        $pokjaI = $master['pokja']->firstWhere('kode', 'I');
        $pokjaII = $master['pokja']->firstWhere('kode', 'II');

        $a = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Fitri Filter Pokja Satu']);
        $b = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Rina Filter Pokja Dua']);

        Keanggotaan::factory()->forOrangKelurahan($a)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaI->id,
            'no_registrasi' => 'F-P1-001',
        ]);
        Keanggotaan::factory()->forOrangKelurahan($b)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaII->id,
            'no_registrasi' => 'F-P2-001',
        ]);

        $response = $this->actingAs($user)->get(route('orang.index', ['pokja_id' => $pokjaI->id]));

        $response->assertOk();
        $response->assertSee('Fitri Filter Pokja Satu', false);
        $response->assertDontSee('Rina Filter Pokja Dua', false);
    }

    public function test_filter_jenis_keanggotaan_mengembalikan_jumlah_baris_tepat(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();

        $tp = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Ibu TP PKK']);
        $kader = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Ibu Kader Umum']);

        Keanggotaan::factory()->forOrangKelurahan($tp)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $master['pokja']->first()->id,
            'no_registrasi' => 'J-TP-001',
        ]);
        Keanggotaan::factory()->forOrangKelurahan($kader)->create([
            'jenis' => Keanggotaan::JENIS_KADER_UMUM,
            'pokja_id' => null,
            'no_registrasi' => 'J-KU-001',
        ]);

        $response = $this->actingAs($user)->get(route('orang.index', ['jenis' => Keanggotaan::JENIS_KADER_UMUM]));

        $response->assertOk();
        $response->assertSee('Ibu Kader Umum', false);
        $response->assertDontSee('Ibu TP PKK', false);
    }

    public function test_halaman_create_mengembalikan_200(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $response = $this->actingAs($user)->get(route('orang.create'));

        $response->assertOk();
        $response->assertSee('Tambah Anggota', false);
        $response->assertSee('Keanggotaan #1', false);
        $response->assertSee('name="keanggotaan[0][jenis]"', false);
    }

    public function test_halaman_edit_mengembalikan_200(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();
        $pokjaI = $master['pokja']->firstWhere('kode', 'I');
        $orang = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Edit Form Orang']);
        Keanggotaan::factory()->forOrangKelurahan($orang)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaI->id,
            'jabatan' => 'Anggota Pokja I',
            'no_registrasi' => 'ED-001',
        ]);

        $response = $this->actingAs($user)->get(route('orang.edit', $orang));

        $response->assertOk();
        $response->assertSee('Ubah Anggota', false);
        $response->assertSee('Edit Form Orang', false);
        $response->assertSee('Keanggotaan #1', false);
        $response->assertSee('name="keanggotaan[0][jenis]"', false);
    }

    public function test_store_orang_dengan_keanggotaan_lengkap_menyimpan_orang_dan_pokja(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();
        $pokjaI = $master['pokja']->firstWhere('kode', 'I');

        $payload = $this->validOrangPayload([
            'nama' => 'Orang E2E Store',
            'keanggotaan' => [
                [
                    'jenis' => Keanggotaan::JENIS_TP_PKK,
                    'pokja_id' => $pokjaI->id,
                    'jabatan' => 'Ketua Pokja I',
                    'no_registrasi' => 'E2E-REG-001',
                    'sk_nomor' => 'SK/001/2024',
                    'mulai' => '2024-01-15',
                    'is_aktif' => '1',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('orang.store'), $payload);

        $response->assertRedirect();
        $orang = Orang::query()->where('nama', 'Orang E2E Store')->first();
        $this->assertNotNull($orang);
        $this->assertSame($master['kelurahan']->id, $orang->kelurahan_id);
        $this->assertDatabaseCount('keanggotaan', 1);
        $this->assertDatabaseHas('keanggotaan', [
            'orang_id' => $orang->id,
            'pokja_id' => $pokjaI->id,
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'jabatan' => 'Ketua Pokja I',
            'no_registrasi' => 'E2E-REG-001',
            'sk_nomor' => 'SK/001/2024',
            'is_aktif' => true,
        ]);
    }

    public function test_halaman_index_dan_show_bisa_dibuka_pengguna_terautentikasi(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();
        $orang = Orang::factory()->forKelurahan($master['kelurahan'])->create(['nama' => 'Budi Santoso']);
        Keanggotaan::factory()->forOrangKelurahan($orang)->create([
            'jenis' => Keanggotaan::JENIS_KADER_UMUM,
            'no_registrasi' => 'SH-001',
        ]);

        $this->actingAs($user)->get(route('orang.index'))->assertOk();
        $this->actingAs($user)->get(route('orang.show', $orang))->assertOk()->assertSee('Budi Santoso', false);
    }

    public function test_tamu_dialihkan_dari_index_dan_show(): void
    {
        $master = $this->seedMaster();
        $orang = Orang::factory()->forKelurahan($master['kelurahan'])->create();

        $this->get(route('orang.index'))->assertRedirect(route('login'));
        $this->get(route('orang.show', $orang))->assertRedirect(route('login'));
    }

    public function test_menghapus_orang_menghapus_keanggotaannya(): void
    {
        $user = $this->actingAdmin();
        $master = $this->seedMaster();
        $orang = Orang::factory()->forKelurahan($master['kelurahan'])->create();
        Keanggotaan::factory()->forOrangKelurahan($orang)->create([
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $master['pokja']->first()->id,
            'no_registrasi' => 'DEL-001',
        ]);

        $this->assertDatabaseCount('keanggotaan', 1);

        $this->actingAs($user)
            ->delete(route('orang.destroy', $orang))
            ->assertRedirect(route('orang.index'));

        $this->assertDatabaseCount('orang', 0);
        $this->assertDatabaseCount('keanggotaan', 0);
    }
}
