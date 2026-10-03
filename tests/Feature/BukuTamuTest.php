<?php

namespace Tests\Feature;

use App\Models\BukuTamu;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BukuTamuTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin-buku-tamu@pkk.test',
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
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'pokja_id' => null,
            'tanggal' => '2026-03-01',
            'nama_tamu' => 'Budi Tamu',
            'alamat' => 'Jl. Contoh',
            'keperluan' => 'Konsultasi',
            'tujuan' => 'Sekretariat',
        ], $overrides);
    }

    public function test_nomor_urut_berurutan_dan_diulang_per_tahun(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-01-10',
            'nama_tamu' => 'A',
        ]);

        BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'no_urut_tahun' => 2,
            'tanggal' => '2026-02-10',
            'nama_tamu' => 'B',
        ]);

        $this->assertSame(3, BukuTamu::nomorUrutBerikutnya($kelurahan->id, null, 2026));
        $this->assertSame(1, BukuTamu::nomorUrutBerikutnya($kelurahan->id, null, 2027));
    }

    public function test_buku_pokja_dan_kelurahan_penomoran_terpisah(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaList] = $this->seedMaster();
        $pokjaId = $pokjaList->first()->id;

        BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'no_urut_tahun' => 4,
            'tanggal' => '2026-03-01',
            'nama_tamu' => 'Kelurahan',
        ]);

        BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => 2026,
            'no_urut_tahun' => 2,
            'tanggal' => '2026-03-02',
            'nama_tamu' => 'Pokja',
        ]);

        $this->assertSame(5, BukuTamu::nomorUrutBerikutnya($kelurahan->id, null, 2026));
        $this->assertSame(3, BukuTamu::nomorUrutBerikutnya($kelurahan->id, $pokjaId, 2026));
    }

    public function test_store_dua_tahun_berbeda_keduanya_nomor_satu(): void
    {
        $user = $this->actingAdmin();
        ['pokja' => $pokjaList] = $this->seedMaster();
        $pokjaId = $pokjaList->first()->id;

        $this->actingAs($user)
            ->post(route('buku-tamu.store'), $this->validPayload([
                'pokja_id' => $pokjaId,
                'tanggal' => '2026-04-01',
                'nama_tamu' => 'Tamu 2026',
            ]))
            ->assertRedirect();

        $tamu2026 = BukuTamu::query()->where('nama_tamu', 'Tamu 2026')->first();
        $this->assertNotNull($tamu2026);
        $this->assertSame(1, $tamu2026->no_urut_tahun);
        $this->assertSame(2026, $tamu2026->tahun);

        $this->actingAs($user)
            ->post(route('buku-tamu.store'), $this->validPayload([
                'pokja_id' => $pokjaId,
                'tanggal' => '2025-05-01',
                'nama_tamu' => 'Tamu 2025',
            ]))
            ->assertRedirect();

        $tamu2025 = BukuTamu::query()->where('nama_tamu', 'Tamu 2025')->first();
        $this->assertNotNull($tamu2025);
        $this->assertSame(1, $tamu2025->no_urut_tahun);
        $this->assertSame(2025, $tamu2025->tahun);
    }

    public function test_validasi_menolak_tanggal_jauh_di_masa_depan_dan_nama_kosong(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)
            ->post(route('buku-tamu.store'), $this->validPayload([
                'tanggal' => now()->addDays(3)->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggal');

        $this->actingAs($user)
            ->post(route('buku-tamu.store'), $this->validPayload([
                'nama_tamu' => '',
            ]))
            ->assertSessionHasErrors('nama_tamu');
    }

    public function test_tamu_dialihkan_ke_login_untuk_semua_rute(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $baris = BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-06-01',
            'nama_tamu' => 'Tamu',
        ]);

        $this->get(route('buku-tamu.index'))->assertRedirect(route('login'));
        $this->get(route('buku-tamu.create'))->assertRedirect(route('login'));
        $this->get(route('buku-tamu.show', $baris))->assertRedirect(route('login'));
        $this->post(route('buku-tamu.store'), [])->assertRedirect(route('login'));
        $this->put(route('buku-tamu.update', $baris), [])->assertRedirect(route('login'));
        $this->delete(route('buku-tamu.destroy', $baris))->assertRedirect(route('login'));
    }

    public function test_pengguna_terautentikasi_bisa_membuka_index_create_show_dan_store(): void
    {
        $user = $this->actingAdmin();
        ['kelurahan' => $kelurahan] = $this->seedMaster();

        $baris = BukuTamu::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-07-01',
            'nama_tamu' => 'Tamu Tampil',
        ]);

        $this->actingAs($user)
            ->get(route('buku-tamu.index'))
            ->assertOk()
            ->assertSee('Buku Tamu');

        $this->actingAs($user)
            ->get(route('buku-tamu.create'))
            ->assertOk()
            ->assertSee('Tambah Buku Tamu');

        $this->actingAs($user)
            ->get(route('buku-tamu.show', $baris))
            ->assertOk()
            ->assertSee('Tamu Tampil');

        $response = $this->actingAs($user)
            ->post(route('buku-tamu.store'), $this->validPayload([
                'nama_tamu' => 'Tamu Baru',
            ]));

        $baru = BukuTamu::query()->where('nama_tamu', 'Tamu Baru')->first();
        $this->assertNotNull($baru);
        $response->assertRedirect(route('buku-tamu.show', $baru));
    }
}
