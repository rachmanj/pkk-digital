<?php

namespace Tests\Feature;

use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Services\KasService;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasTest extends TestCase
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
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-03-15',
            'sumber_dana' => 'Iuran',
            'uraian' => 'Penerimaan contoh',
            'no_bukti' => 'BK-001',
            'jumlah' => '100000',
        ], $overrides);
    }

    public function test_invarian_saldo_akhir_tunai_dan_bank(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $service = app(KasService::class);

        KasSaldoAwal::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'pos' => KasSaldoAwal::POS_TUNAI,
            'jumlah' => 50000,
        ]);
        KasSaldoAwal::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'pos' => KasSaldoAwal::POS_BANK,
            'jumlah' => 200000,
        ]);

        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-01-10',
            'uraian' => 'Masuk tunai',
            'jumlah' => 30000,
        ]);
        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_KELUAR,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-02-10',
            'uraian' => 'Keluar tunai',
            'jumlah' => 10000,
        ]);
        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_BANK,
            'tanggal' => '2026-03-10',
            'uraian' => 'Masuk bank',
            'jumlah' => 50000,
        ]);

        foreach ([KasTransaksi::POS_TUNAI, KasTransaksi::POS_BANK] as $pos) {
            $awal = $service->saldoAwal($kelurahan->id, null, 2026, $pos);
            $masuk = $service->totalMasuk($kelurahan->id, null, 2026, $pos);
            $keluar = $service->totalKeluar($kelurahan->id, null, 2026, $pos);
            $akhir = $service->saldoAkhir($kelurahan->id, null, 2026, $pos);
            $this->assertEqualsWithDelta($awal + $masuk - $keluar, $akhir, 0.001);
        }

        $ringkasan = $service->ringkasan($kelurahan->id, null, 2026);
        $this->assertEqualsWithDelta(
            $ringkasan['tunai']['saldo_akhir'] + $ringkasan['bank']['saldo_akhir'],
            $ringkasan['total']['saldo_akhir'],
            0.001
        );
    }

    public function test_transaksi_keluar_mengurangi_saldo(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $service = app(KasService::class);

        KasSaldoAwal::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'pos' => KasSaldoAwal::POS_TUNAI,
            'jumlah' => 100000,
        ]);

        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_KELUAR,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-04-01',
            'uraian' => 'Belanja',
            'jumlah' => 25000,
        ]);

        $this->assertSame(75000.0, $service->saldoAkhir($kelurahan->id, null, 2026, KasTransaksi::POS_TUNAI));
    }

    public function test_rekap_bulanan_dan_saldo_berjalan(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $service = app(KasService::class);

        KasSaldoAwal::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'pos' => KasSaldoAwal::POS_TUNAI,
            'jumlah' => 10000,
        ]);

        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-03-05',
            'uraian' => 'Maret masuk',
            'jumlah' => 5000,
        ]);
        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_KELUAR,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-03-20',
            'uraian' => 'Maret keluar',
            'jumlah' => 2000,
        ]);

        $rekap = $service->rekapBulanan($kelurahan->id, null, 2026, KasTransaksi::POS_TUNAI);
        $this->assertCount(12, $rekap);
        $this->assertSame(5000.0, $rekap[2]['masuk']);
        $this->assertSame(2000.0, $rekap[2]['keluar']);
        $this->assertSame(13000.0, $rekap[2]['saldo_akhir']);
        $this->assertSame(10000.0, $rekap[1]['saldo_akhir']);
    }

    public function test_tahun_terpisah_dan_pemisahan_tunai_bank(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $service = app(KasService::class);

        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2025,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2025-12-31',
            'uraian' => 'Tahun lalu',
            'jumlah' => 999999,
        ]);
        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_BANK,
            'tanggal' => '2026-01-01',
            'uraian' => 'Bank saja',
            'jumlah' => 40000,
        ]);

        $this->assertSame(0.0, $service->totalMasuk($kelurahan->id, null, 2026, KasTransaksi::POS_TUNAI));
        $this->assertSame(40000.0, $service->totalMasuk($kelurahan->id, null, 2026, KasTransaksi::POS_BANK));
        $this->assertSame(0.0, $service->totalMasuk($kelurahan->id, null, 2025, KasTransaksi::POS_BANK));
    }

    public function test_ketua_hanya_lihat_tidak_bisa_simpan_atau_hapus(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $ketua = $this->userForRole('ketua', ['email' => 'ketua-kas@pkk.test']);
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-kas@pkk.test']);

        $transaksi = KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-05-01',
            'uraian' => 'Test',
            'jumlah' => 1000,
            'created_by' => $sekretaris->id,
        ]);

        $this->actingAs($ketua)->get(route('kas.index'))->assertOk();
        $this->actingAs($ketua)->post(route('kas.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($ketua)->delete(route('kas.destroy', $transaksi))->assertForbidden();
    }

    public function test_kader_ditolak_membuka_buku_kas(): void
    {
        $this->seedMaster();
        $kader = $this->userForRole('kader', ['email' => 'kader-kas@pkk.test']);

        $this->actingAs($kader)->get(route('kas.index'))->assertForbidden();
    }

    public function test_ketua_pokja_i_akses_kelurahan_dan_pokja_i_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI, 'pokjaIi' => $pokjaIi] = $this->seedMaster();
        $ketuaPokja = $this->userForRole('ketua_pokja', [
            'email' => 'kp-kas@pkk.test',
            'pokja_id' => $pokjaI->id,
        ]);

        $trxKelurahan = KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-06-01',
            'uraian' => 'Kelurahan',
            'jumlah' => 1000,
        ]);
        $trxI = KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaI->id,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-06-02',
            'uraian' => 'Pokja I',
            'jumlah' => 2000,
        ]);
        $trxIi = KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaIi->id,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-06-03',
            'uraian' => 'Pokja II',
            'jumlah' => 3000,
        ]);

        $this->actingAs($ketuaPokja)->get(route('kas.index', ['buku' => 'kelurahan']))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kas.index', ['buku' => 'pokja-'.$pokjaI->id]))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kas.index', ['buku' => 'pokja-'.$pokjaIi->id]))->assertForbidden();
        $this->actingAs($ketuaPokja)->get(route('kas.show', $trxKelurahan))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kas.show', $trxI))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kas.show', $trxIi))->assertForbidden();
    }

    public function test_jumlah_nol_dan_negatif_ditolak(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-valid@pkk.test']);

        $this->actingAs($sekretaris)->post(route('kas.store'), $this->validPayload(['jumlah' => '0']))
            ->assertSessionHasErrors('jumlah');
        $this->actingAs($sekretaris)->post(route('kas.store'), $this->validPayload(['jumlah' => '-100']))
            ->assertSessionHasErrors('jumlah');
    }

    public function test_tanggal_wajib(): void
    {
        $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-tgl@pkk.test']);

        $payload = $this->validPayload();
        unset($payload['tanggal']);

        $this->actingAs($sekretaris)->post(route('kas.store'), $payload)
            ->assertSessionHasErrors('tanggal');
    }

    public function test_tamu_dialihkan_ke_login(): void
    {
        $this->seedMaster();

        $this->get(route('kas.index'))->assertRedirect(route('login'));
    }

    public function test_halaman_crud_200_untuk_pengguna_berhak(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sek-crud@pkk.test']);

        $this->actingAs($sekretaris)->get(route('kas.index'))->assertOk();
        $this->actingAs($sekretaris)->get(route('kas.create'))->assertOk();

        $response = $this->actingAs($sekretaris)->post(route('kas.store'), $this->validPayload());
        $response->assertRedirect();
        $transaksi = KasTransaksi::query()->where('kelurahan_id', $kelurahan->id)->latest('id')->first();
        $this->assertNotNull($transaksi);

        $this->actingAs($sekretaris)->get(route('kas.show', $transaksi))->assertOk();
        $this->actingAs($sekretaris)->get(route('kas.edit', $transaksi))->assertOk();
    }
}
