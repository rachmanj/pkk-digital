<?php

namespace Tests\Feature;

use App\Models\AgendaSurat;
use App\Models\Disposisi;
use App\Models\InventarisBarang;
use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Models\Keanggotaan;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\Presensi;
use App\Models\ProgramKerja;
use App\Models\StrukturPengurus;
use App\Models\User;
use App\Services\LaporanKotaService;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaporanKotaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kelurahan: Kelurahan, pokjaI: Pokja}
     */
    private function seedMaster(): array
    {
        $this->seed(MasterSeeder::class);
        $this->seedRoles();
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokjaI = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'I')->firstOrFail();

        return ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI];
    }

    /**
     * @return array<string, mixed>
     */
    private function bangunDataLaporan(Kelurahan $kelurahan, Pokja $pokjaI): array
    {
        $orangA = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Orang Laporan A']);
        $orangB = Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Orang Laporan B']);
        Orang::factory()->forKelurahan($kelurahan)->create(['nama' => 'Orang Laporan C']);

        Keanggotaan::query()->create([
            'orang_id' => $orangA->id,
            'kelurahan_id' => $kelurahan->id,
            'jenis' => Keanggotaan::JENIS_TP_PKK,
            'pokja_id' => $pokjaI->id,
            'is_aktif' => true,
        ]);
        Keanggotaan::query()->create([
            'orang_id' => $orangB->id,
            'kelurahan_id' => $kelurahan->id,
            'jenis' => Keanggotaan::JENIS_KADER_UMUM,
            'pokja_id' => null,
            'is_aktif' => true,
        ]);

        $kegiatanMaret = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan Maret',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-03-10',
            'tempat' => 'Aula',
            'acara' => 'Rapat',
        ]);
        $kegiatanApril = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan April',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-04-05',
            'tempat' => 'Balai',
            'acara' => 'Sosialisasi',
        ]);
        Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'nama' => 'Kegiatan 2025',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2025-03-10',
            'tempat' => 'Lama',
            'acara' => 'Tahun lalu',
        ]);

        Presensi::query()->create([
            'kegiatan_id' => $kegiatanMaret->id,
            'orang_id' => $orangA->id,
            'urut' => 1,
            'hadir' => true,
        ]);
        Presensi::query()->create([
            'kegiatan_id' => $kegiatanMaret->id,
            'orang_id' => $orangB->id,
            'urut' => 2,
            'hadir' => true,
        ]);
        Presensi::query()->create([
            'kegiatan_id' => $kegiatanApril->id,
            'orang_id' => $orangA->id,
            'urut' => 1,
            'hadir' => true,
        ]);

        $suratMasuk = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'no_surat' => 'SM/1',
            'tanggal_surat' => '2026-03-12',
            'tanggal_terima' => '2026-03-12',
            'perihal' => 'Undangan',
            'dari' => 'Dinas',
        ]);
        AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'jenis' => AgendaSurat::JENIS_KELUAR,
            'tahun' => 2026,
            'no_urut_tahun' => 1,
            'no_surat' => 'SK/1',
            'tanggal_surat' => '2026-03-20',
            'perihal' => 'Balasan',
            'kepada' => 'Dinas',
        ]);
        Disposisi::query()->create([
            'agenda_surat_id' => $suratMasuk->id,
            'pokja_id' => $pokjaI->id,
            'instruksi' => 'Tindaklanjuti',
            'status' => Disposisi::STATUS_PROSES,
        ]);

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
            'jenis' => KasTransaksi::JENIS_MASUK,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-03-05',
            'uraian' => 'Iuran',
            'jumlah' => 50000,
        ]);
        KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'jenis' => KasTransaksi::JENIS_KELUAR,
            'pos' => KasTransaksi::POS_TUNAI,
            'tanggal' => '2026-03-15',
            'uraian' => 'Belanja',
            'jumlah' => 20000,
        ]);

        InventarisBarang::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'nama_barang' => 'Meja',
            'tanggal_terima' => '2026-01-01',
            'jumlah' => 2,
            'kondisi' => InventarisBarang::KONDISI_BAIK,
        ]);
        InventarisBarang::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'nama_barang' => 'Kursi',
            'tanggal_terima' => '2026-01-01',
            'jumlah' => 1,
            'kondisi' => InventarisBarang::KONDISI_RUSAK_RINGAN,
        ]);

        $pkDenganRealisasi = ProgramKerja::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'program' => 'Program A',
            'kegiatan' => 'Kegiatan PK',
            'bulan_rencana' => [3],
            'bulan_pelaksanaan' => [3],
        ]);
        ProgramKerja::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'tahun' => 2026,
            'program' => 'Program B',
            'kegiatan' => 'Belum realisasi',
            'bulan_rencana' => [4],
        ]);
        $kegiatanMaret->update(['program_kerja_id' => $pkDenganRealisasi->id]);

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'jabatan' => 'Ketua TP PKK',
            'nama' => 'Ibu Ketua Uji',
            'urutan' => 1,
        ]);
        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => StrukturPengurus::UNIT_TP_PKK,
            'jabatan' => 'Sekretaris',
            'nama' => 'Ibu Sekretaris Uji',
            'urutan' => 2,
        ]);

        return [
            'jumlah_orang' => 3,
            'keanggotaan_pokja_i' => 1,
            'jumlah_kader' => 1,
            'kegiatan_maret' => 1,
            'hadir_maret' => 2,
            'kas_saldo_awal_maret' => 100000.0,
            'kas_masuk_maret' => 50000.0,
            'kas_keluar_maret' => 20000.0,
            'kas_saldo_akhir_maret' => 130000.0,
        ];
    }

    public function test_angka_laporan_bulan_maret_sesuai_perhitungan_manual(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();
        $manual = $this->bangunDataLaporan($kelurahan, $pokjaI);

        $service = app(LaporanKotaService::class);
        $filter = $service->normalisasiFilter(['tahun' => 2026, 'bulan' => 3]);
        $dataset = $service->dataset($filter, $kelurahan);
        $laporan = $dataset['laporan'];

        $this->assertSame($manual['jumlah_orang'], $laporan['keanggotaan']['jumlah_orang_terdaftar']);
        $this->assertSame($manual['jumlah_kader'], $laporan['keanggotaan']['jumlah_kader']);
        $this->assertSame($manual['keanggotaan_pokja_i'], $laporan['keanggotaan']['keanggotaan_aktif_per_pokja'][0]['jumlah_aktif']);
        $this->assertSame($manual['kegiatan_maret'], $laporan['kegiatan']['jumlah_kegiatan']);
        $this->assertSame($manual['hadir_maret'], $laporan['kegiatan']['total_peserta_hadir']);
        $this->assertSame(1, $laporan['agenda_surat']['surat_masuk']);
        $this->assertSame(1, $laporan['agenda_surat']['surat_keluar']);
        $this->assertSame(1, $laporan['agenda_surat']['disposisi_belum_selesai']);
        $this->assertSame($manual['kas_saldo_awal_maret'], $laporan['keuangan']['tunai_nilai']['saldo_awal']);
        $this->assertSame($manual['kas_masuk_maret'], $laporan['keuangan']['tunai_nilai']['masuk']);
        $this->assertSame($manual['kas_keluar_maret'], $laporan['keuangan']['tunai_nilai']['keluar']);
        $this->assertSame($manual['kas_saldo_akhir_maret'], $laporan['keuangan']['tunai_nilai']['saldo_akhir']);
        $this->assertSame(2, $laporan['inventaris']['jumlah_jenis_barang']);
        $this->assertSame(3, $laporan['inventaris']['total_unit']);
        $this->assertSame(2, $laporan['inventaris']['per_kondisi'][InventarisBarang::KONDISI_BAIK]);
        $this->assertSame(1, $laporan['program_kerja']['jumlah_butir']);
        $this->assertSame(1, $laporan['program_kerja']['jumlah_dengan_realisasi']);
        $this->assertSame(1, $laporan['program_kerja']['jumlah_kegiatan_tertaut']);
        $this->assertSame('Ibu Ketua Uji', $laporan['struktur']['ketua']);
    }

    public function test_laporan_kosong_menampilkan_nol_tanpa_error(): void
    {
        ['kelurahan' => $kelurahan] = $this->seedMaster();
        $admin = $this->actingAdmin();

        $response = $this->actingAs($admin)->get(route('laporan-kota.index', ['tahun' => 2026]));
        $response->assertOk();
        $response->assertSee('Jumlah kegiatan', false);
        $response->assertSee('0', false);

        $service = app(LaporanKotaService::class);
        $dataset = $service->dataset($service->normalisasiFilter(['tahun' => 2026]), $kelurahan);
        $this->assertSame(0, $dataset['laporan']['kegiatan']['jumlah_kegiatan']);
        $this->assertSame(0, $dataset['laporan']['agenda_surat']['surat_masuk']);
    }

    public function test_filter_bulan_tidak_mencampur_bulan_lain_dan_tahun_lain(): void
    {
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();
        $this->bangunDataLaporan($kelurahan, $pokjaI);

        $service = app(LaporanKotaService::class);
        $maret = $service->dataset($service->normalisasiFilter(['tahun' => 2026, 'bulan' => 3]), $kelurahan);
        $april = $service->dataset($service->normalisasiFilter(['tahun' => 2026, 'bulan' => 4]), $kelurahan);

        $this->assertSame(1, $maret['laporan']['kegiatan']['jumlah_kegiatan']);
        $this->assertSame(1, $april['laporan']['kegiatan']['jumlah_kegiatan']);
        $this->assertSame(0, $april['laporan']['agenda_surat']['surat_masuk']);
        $this->assertSame(1, $service->dataset($service->normalisasiFilter(['tahun' => 2025, 'bulan' => 3]), $kelurahan)['laporan']['kegiatan']['jumlah_kegiatan']);
        $this->assertSame(0, $service->dataset($service->normalisasiFilter(['tahun' => 2025, 'bulan' => 4]), $kelurahan)['laporan']['kegiatan']['jumlah_kegiatan']);
    }

    public function test_halaman_web_cetak_pdf_dan_export_mengembalikan_200_dan_tipe_konten(): void
    {
        $admin = $this->actingAdmin();
        ['kelurahan' => $kelurahan, 'pokjaI' => $pokjaI] = $this->seedMaster();
        $this->bangunDataLaporan($kelurahan, $pokjaI);

        $params = ['tahun' => 2026, 'bulan' => 3];

        $web = $this->actingAs($admin)->get(route('laporan-kota.index', $params));
        $web->assertOk();
        $web->assertHeader('content-type', 'text/html; charset=utf-8');

        $cetak = $this->actingAs($admin)->get(route('cetak.show', ['buku' => 'laporan_kota'] + $params));
        $cetak->assertOk();
        $cetak->assertHeader('content-type', 'text/html; charset=utf-8');

        $pdf = $this->actingAs($admin)->get(route('cetak.pdf', ['buku' => 'laporan_kota'] + $params));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $export = $this->actingAs($admin)->get(route('export.buku', ['buku' => 'laporan_kota'] + $params));
        $export->assertOk();
        $this->assertStringContainsString('spreadsheet', (string) $export->headers->get('content-type'));

        $file = $export->baseResponse->getFile();
        $spreadsheet = IOFactory::load($file->getPathname());
        $this->assertSame('Identitas', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Keanggotaan', $spreadsheet->getSheet(1)->getTitle());
    }

    public function test_kader_boleh_melihat_laporan_tamu_dialihkan_ke_login(): void
    {
        $this->seedMaster();
        $kader = User::query()->where('username', 'kader')->firstOrFail();

        $this->actingAs($kader)->get(route('laporan-kota.index', ['tahun' => 2026]))->assertOk();

        auth()->logout();

        $this->get(route('laporan-kota.index'))->assertRedirect(route('login'));

        $tanpaIzin = User::factory()->create([
            'username' => 'tanpa_izin_laporan',
            'email' => 'tanpa-izin-laporan@pkk.test',
        ]);
        $this->actingAs($tanpaIzin)->get(route('laporan-kota.index', ['tahun' => 2026]))->assertForbidden();
    }

    public function test_bendahara_boleh_mengakses_laporan_kota(): void
    {
        $this->seedMaster();
        $bendahara = User::query()->where('username', 'bendahara')->firstOrFail();

        $this->actingAs($bendahara)->get(route('laporan-kota.index', ['tahun' => 2026]))->assertOk();
        $this->actingAs($bendahara)->get(route('cetak.show', ['buku' => 'laporan_kota', 'tahun' => 2026]))->assertOk();
    }

    public function test_laporan_superadmin_mengikuti_kelurahan_aktif(): void
    {
        ['kelurahan' => $gsi] = $this->seedMaster();
        $lain = Kelurahan::query()->create([
            'kode' => 'LAIN-LAP',
            'nama' => 'Kelurahan Laporan Lain',
            'kecamatan' => 'Kecamatan Lain',
            'kota' => 'Kota Lain',
            'provinsi' => 'Prov',
            'is_active' => false,
        ]);

        Orang::factory()->forKelurahan($gsi)->create(['nama' => 'Khusus GSI']);
        Orang::factory()->forKelurahan($lain)->create(['nama' => 'Khusus Lain']);
        Orang::factory()->forKelurahan($lain)->create(['nama' => 'Kedua Lain']);

        $admin = $this->actingAdmin();
        $service = app(LaporanKotaService::class);

        $this->actingAs($admin)->post(route('kelurahan-aktif.update'), ['kelurahan_id' => $lain->id])->assertRedirect();

        $dataset = $service->dataset($service->normalisasiFilter(['tahun' => 2026]), $lain);
        $this->assertSame('Kelurahan Laporan Lain', $dataset['laporan']['identitas']['nama_kelurahan']);
        $this->assertSame(2, $dataset['laporan']['keanggotaan']['jumlah_orang_terdaftar']);
    }

    public function test_kop_cetak_mengikuti_perpindahan_kelurahan_aktif_superadmin(): void
    {
        $this->seedMaster();
        $lain = Kelurahan::query()->create([
            'kode' => 'KOP-LAIN',
            'nama' => 'Kelurahan Kop Baru',
            'kecamatan' => 'Kecamatan Kop',
            'kota' => 'Kota Kop',
            'provinsi' => 'Prov',
            'is_active' => false,
        ]);

        $admin = $this->actingAdmin();

        $awal = $this->actingAs($admin)->get(route('cetak.show', ['buku' => 'buku_tamu', 'tahun' => 2026]));
        $awal->assertOk();
        $awal->assertSee('Gunung Sari Ilir', false);
        $awal->assertDontSee('Kelurahan Kop Baru', false);

        $this->actingAs($admin)->post(route('kelurahan-aktif.update'), ['kelurahan_id' => $lain->id])->assertRedirect();

        $sesudah = $this->actingAs($admin)->get(route('cetak.show', ['buku' => 'buku_tamu', 'tahun' => 2026]));
        $sesudah->assertOk();
        $sesudah->assertSee('Kelurahan Kop Baru', false);
        $sesudah->assertSee('Kecamatan Kop', false);
        $sesudah->assertDontSee('Gunung Sari Ilir', false);
    }
}
