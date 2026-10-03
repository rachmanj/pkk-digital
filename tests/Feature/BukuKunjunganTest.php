<?php

namespace Tests\Feature;

use App\Models\BukuKunjungan;
use App\Models\Kelurahan;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BukuKunjunganTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        return User::factory()->create([
            'email' => 'admin-buku-kunjungan@pkk.test',
            'password' => 'password',
        ]);
    }

    private function seedMaster(): Kelurahan
    {
        $this->seed(MasterSeeder::class);

        return Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'tanggal' => '2026-03-01',
            'nama' => 'Ibu Sekretaris',
            'jabatan' => 'Sekretaris',
            'lokasi_kunjungan' => 'Kantor Kelurahan',
            'jenis_kegiatan' => 'Rapat koordinasi',
        ], $overrides);
    }

    public function test_nomor_urut_berurutan_dan_diulang_per_tahun(): void
    {
        $kelurahan = $this->seedMaster();

        BukuKunjungan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-01-10',
            'nama' => 'A',
        ]);

        BukuKunjungan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'tahun' => 2026,
            'no_urut_tahun' => 2,
            'tanggal' => '2026-02-10',
            'nama' => 'B',
        ]);

        $this->assertSame(3, BukuKunjungan::nomorUrutBerikutnya($kelurahan->id, 2026));
        $this->assertSame(1, BukuKunjungan::nomorUrutBerikutnya($kelurahan->id, 2027));
    }

    public function test_store_dua_tahun_berbeda_keduanya_nomor_satu(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)
            ->post(route('buku-kunjungan.store'), $this->validPayload([
                'tanggal' => '2026-04-01',
                'nama' => 'Kunjungan 2026',
            ]))
            ->assertRedirect();

        $baris2026 = BukuKunjungan::query()->where('nama', 'Kunjungan 2026')->first();
        $this->assertNotNull($baris2026);
        $this->assertSame(1, $baris2026->no_urut_tahun);
        $this->assertSame(2026, $baris2026->tahun);

        $this->actingAs($user)
            ->post(route('buku-kunjungan.store'), $this->validPayload([
                'tanggal' => '2025-05-01',
                'nama' => 'Kunjungan 2025',
            ]))
            ->assertRedirect();

        $baris2025 = BukuKunjungan::query()->where('nama', 'Kunjungan 2025')->first();
        $this->assertNotNull($baris2025);
        $this->assertSame(1, $baris2025->no_urut_tahun);
        $this->assertSame(2025, $baris2025->tahun);
    }

    public function test_validasi_menolak_tanggal_jauh_di_masa_depan_dan_nama_kosong(): void
    {
        $user = $this->actingAdmin();
        $this->seedMaster();

        $this->actingAs($user)
            ->post(route('buku-kunjungan.store'), $this->validPayload([
                'tanggal' => now()->addDays(3)->toDateString(),
            ]))
            ->assertSessionHasErrors('tanggal');

        $this->actingAs($user)
            ->post(route('buku-kunjungan.store'), $this->validPayload([
                'nama' => '',
            ]))
            ->assertSessionHasErrors('nama');
    }

    public function test_tamu_dialihkan_ke_login_untuk_semua_rute(): void
    {
        $kelurahan = $this->seedMaster();
        $baris = BukuKunjungan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-06-01',
            'nama' => 'Kunjungan',
        ]);

        $this->get(route('buku-kunjungan.index'))->assertRedirect(route('login'));
        $this->get(route('buku-kunjungan.create'))->assertRedirect(route('login'));
        $this->get(route('buku-kunjungan.show', $baris))->assertRedirect(route('login'));
        $this->post(route('buku-kunjungan.store'), [])->assertRedirect(route('login'));
        $this->put(route('buku-kunjungan.update', $baris), [])->assertRedirect(route('login'));
        $this->delete(route('buku-kunjungan.destroy', $baris))->assertRedirect(route('login'));
    }

    public function test_pengguna_terautentikasi_bisa_membuka_index_create_show_dan_store(): void
    {
        $user = $this->actingAdmin();
        $kelurahan = $this->seedMaster();

        $baris = BukuKunjungan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'tanggal' => '2026-07-01',
            'nama' => 'Kunjungan Tampil',
        ]);

        $this->actingAs($user)
            ->get(route('buku-kunjungan.index'))
            ->assertOk()
            ->assertSee('Buku Kunjungan');

        $this->actingAs($user)
            ->get(route('buku-kunjungan.create'))
            ->assertOk()
            ->assertSee('Tambah Buku Kunjungan');

        $this->actingAs($user)
            ->get(route('buku-kunjungan.show', $baris))
            ->assertOk()
            ->assertSee('Kunjungan Tampil');

        $response = $this->actingAs($user)
            ->post(route('buku-kunjungan.store'), $this->validPayload([
                'nama' => 'Kunjungan Baru',
            ]));

        $baru = BukuKunjungan::query()->where('nama', 'Kunjungan Baru')->first();
        $this->assertNotNull($baru);
        $response->assertRedirect(route('buku-kunjungan.show', $baru));
    }
}
