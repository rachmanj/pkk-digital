<?php

namespace Tests\Feature;

use App\Exports\InventarisExport;
use App\Models\InventarisBarang;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class InventarisTest extends TestCase
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
            'nama_barang' => 'Meja Rapat',
            'asal_barang' => 'Pembelian',
            'tanggal_terima' => '2026-04-10',
            'jumlah' => 3,
            'tempat_penyimpanan' => 'Ruang sekretariat',
            'kondisi' => InventarisBarang::KONDISI_BAIK,
            'keterangan' => 'Contoh',
        ], $overrides);
    }

    private function buatBarang(Kelurahan $kelurahan, ?int $pokjaId, array $attrs = []): InventarisBarang
    {
        return InventarisBarang::query()->create(array_merge([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => 2026,
            'nama_barang' => 'Barang default',
            'asal_barang' => null,
            'tanggal_terima' => '2026-01-15',
            'jumlah' => 1,
            'tempat_penyimpanan' => null,
            'kondisi' => InventarisBarang::KONDISI_BAIK,
            'keterangan' => null,
        ], $attrs));
    }

    public function test_halaman_crud_200_untuk_pengguna_berhak(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-inv@pkk.test']);

        $this->actingAs($sekretaris)->get(route('inventaris.index'))->assertOk();
        $this->actingAs($sekretaris)->get(route('inventaris.create'))->assertOk();

        $response = $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload());
        $response->assertRedirect();
        $barang = InventarisBarang::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->first();
        $this->assertNotNull($barang);
        $this->assertSame(2026, $barang->tahun);

        $this->actingAs($sekretaris)->get(route('inventaris.show', $barang))->assertOk();
        $this->actingAs($sekretaris)->get(route('inventaris.edit', $barang))->assertOk();
    }

    public function test_kader_boleh_lihat_tapi_403_simpan_dan_hapus(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-inv@pkk.test']);
        $barang = $this->buatBarang($kelurahan, null, ['nama_barang' => 'Kursi']);

        $this->actingAs($kader)->get(route('inventaris.index'))->assertOk();
        $this->actingAs($kader)->post(route('inventaris.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($kader)->delete(route('inventaris.destroy', $barang))->assertForbidden();
    }

    public function test_ketua_hanya_lihat_403_simpan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $ketua = $this->userForRole('ketua', ['email' => 'ketua-inv@pkk.test']);
        $barang = $this->buatBarang($kelurahan, null);

        $this->actingAs($ketua)->get(route('inventaris.index'))->assertOk();
        $this->actingAs($ketua)->post(route('inventaris.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($ketua)->delete(route('inventaris.destroy', $barang))->assertForbidden();
    }

    public function test_ketua_pokja_i_akses_kelurahan_dan_pokja_i_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI, 'pokjaIi' => $pokjaIi] = $this->seedMaster();
        $ketuaPokja = $this->userForRole('ketua_pokja', [
            'email' => 'kp-inv@pkk.test',
            'pokja_id' => $pokjaI->id,
        ]);

        $barangKelurahan = $this->buatBarang($kelurahan, null, ['nama_barang' => 'Kelurahan']);
        $barangI = $this->buatBarang($kelurahan, $pokjaI->id, ['nama_barang' => 'Pokja I']);
        $barangIi = $this->buatBarang($kelurahan, $pokjaIi->id, ['nama_barang' => 'Pokja II']);

        $this->actingAs($ketuaPokja)->get(route('inventaris.index', ['buku' => 'kelurahan']))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('inventaris.index', ['buku' => 'pokja-'.$pokjaI->id]))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('inventaris.index', ['buku' => 'pokja-'.$pokjaIi->id]))->assertForbidden();
        $this->actingAs($ketuaPokja)->get(route('inventaris.show', $barangKelurahan))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('inventaris.show', $barangI))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('inventaris.show', $barangIi))->assertForbidden();
    }

    public function test_validasi_nama_jumlah_tanggal_dan_kondisi(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-valid-inv@pkk.test']);

        $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload(['nama_barang' => '']))
            ->assertSessionHasErrors('nama_barang');
        $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload(['jumlah' => 0]))
            ->assertSessionHasErrors('jumlah');
        $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload(['tanggal_terima' => '']))
            ->assertSessionHasErrors('tanggal_terima');
        $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload(['kondisi' => 'hancur']))
            ->assertSessionHasErrors('kondisi');
    }

    public function test_simpan_dan_ubah_mengisi_tahun_dari_tanggal(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-tahun-inv@pkk.test']);

        $this->actingAs($sekretaris)->post(route('inventaris.store'), $this->validPayload([
            'tanggal_terima' => '2025-11-20',
        ]))->assertRedirect();

        $barang = InventarisBarang::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->firstOrFail();
        $this->assertSame(2025, $barang->tahun);

        $this->actingAs($sekretaris)->put(route('inventaris.update', $barang), $this->validPayload([
            'tanggal_terima' => '2026-08-01',
            'nama_barang' => 'Meja Diubah',
        ]))->assertRedirect();

        $barang->refresh();
        $this->assertSame(2026, $barang->tahun);
        $this->assertSame('Meja Diubah', $barang->nama_barang);
    }

    public function test_pencarian_dan_filter_kondisi(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-filter-inv@pkk.test']);

        $this->buatBarang($kelurahan, null, [
            'nama_barang' => 'Komputer Kantor',
            'asal_barang' => 'Hibah',
            'kondisi' => InventarisBarang::KONDISI_BAIK,
        ]);
        $this->buatBarang($kelurahan, null, [
            'nama_barang' => 'Printer Lama',
            'tempat_penyimpanan' => 'Gudang belakang',
            'kondisi' => InventarisBarang::KONDISI_RUSAK_BERAT,
        ]);

        $this->actingAs($sekretaris)->get(route('inventaris.index', [
            'tahun' => 2026,
            'buku' => 'kelurahan',
            'q' => 'Komputer',
        ]))
            ->assertOk()
            ->assertSee('Komputer Kantor', false)
            ->assertDontSee('Printer Lama', false);

        $this->actingAs($sekretaris)->get(route('inventaris.index', [
            'tahun' => 2026,
            'buku' => 'kelurahan',
            'kondisi' => InventarisBarang::KONDISI_RUSAK_BERAT,
        ]))
            ->assertOk()
            ->assertSee('Printer Lama', false)
            ->assertDontSee('Komputer Kantor', false);
    }

    public function test_halaman_cetak_memuat_judul_kolom_termasuk_salah_ketik_asli(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-cetak-inv@pkk.test']);
        $query = http_build_query(['tahun' => 2026, 'buku' => 'kelurahan']);

        $response = $this->actingAs($sekretaris)->get(route('cetak.show', ['buku' => 'buku_inventaris']).'?'.$query);
        $response->assertOk();
        $response->assertSee('TANGGAL PENEIMAAN/PEMBELIAN', false);
        $response->assertSee('NAMA BARANG', false);
        $response->assertSee('KONDISI BARANG', false);
    }

    public function test_export_excel_memuat_urutan_header_config(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-export-inv@pkk.test']);

        $this->buatBarang($kelurahan, null, ['nama_barang' => 'Export Test']);

        Excel::fake();

        $query = http_build_query(['tahun' => 2026, 'buku' => 'kelurahan']);
        $this->actingAs($sekretaris)->get(route('export.buku', ['buku' => 'buku_inventaris']).'?'.$query)->assertOk();

        Excel::assertDownloaded('buku_inventaris-2026.xlsx', function (InventarisExport $export) {
            $expected = array_column(config('buku.buku_inventaris.kolom'), 'label');
            $this->assertSame($expected, $export->headings());

            return true;
        });
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->seedMaster();

        $this->get(route('inventaris.index'))->assertRedirect(route('login'));
    }
}
