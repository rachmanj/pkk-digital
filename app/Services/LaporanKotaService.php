<?php

namespace App\Services;

use App\Models\AgendaSurat;
use App\Models\Disposisi;
use App\Models\InventarisBarang;
use App\Models\Keanggotaan;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\ProgramKerja;
use App\Models\StrukturPengurus;
use App\Support\FormatTanggalIndonesia;
use App\Support\FormatUang;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LaporanKotaService
{
    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    public function dataset(array $filter, ?Kelurahan $kelurahan, string $kode = 'laporan_kota'): array
    {
        $config = config('buku.'.$kode);
        $filter = $this->normalisasiFilter($filter);

        if ($kelurahan === null) {
            abort(422, 'Belum ada kelurahan aktif.');
        }

        $laporan = [
            'identitas' => $this->bagianIdentitas($kelurahan, $filter),
            'keanggotaan' => $this->bagianKeanggotaan($kelurahan),
            'kegiatan' => $this->bagianKegiatan($kelurahan, $filter),
            'agenda_surat' => $this->bagianAgendaSurat($kelurahan, $filter),
            'keuangan' => $this->bagianKeuangan($kelurahan, $filter),
            'inventaris' => $this->bagianInventaris($kelurahan),
            'program_kerja' => $this->bagianProgramKerja($kelurahan, $filter),
            'struktur' => $this->bagianStruktur($kelurahan),
        ];

        return [
            'kode' => $kode,
            'judul' => $config['judul'] ?? 'Laporan ke PKK Kota',
            'tipe' => 'laporan_kota',
            'tahun' => $filter['tahun'],
            'bulan' => $filter['bulan'],
            'label_periode' => $this->labelPeriode($filter),
            'kolom' => [],
            'tanda_tangan' => $config['tanda_tangan'] ?? [],
            'baris' => [],
            'laporan' => $laporan,
            'kelurahan' => [
                'nama' => $kelurahan->nama,
                'kecamatan' => $kelurahan->kecamatan,
                'kota' => $kelurahan->kota,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    public function normalisasiFilter(array $filter): array
    {
        $tahun = (int) ($filter['tahun'] ?? now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $bulan = isset($filter['bulan']) && $filter['bulan'] !== '' && $filter['bulan'] !== null
            ? (int) $filter['bulan']
            : null;
        if ($bulan !== null && ($bulan < 1 || $bulan > 12)) {
            $bulan = null;
        }

        $dari = $filter['dari'] ?? null;
        $sampai = $filter['sampai'] ?? null;
        if ($bulan !== null) {
            $dari = Carbon::create($tahun, $bulan, 1)->startOfDay();
            $sampai = $dari->copy()->endOfMonth()->startOfDay();
        } else {
            $dari = $this->parseTanggal($dari);
            $sampai = $this->parseTanggal($sampai);
        }

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'dari' => $dari,
            'sampai' => $sampai,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, string>
     */
    private function bagianIdentitas(Kelurahan $kelurahan, array $filter): array
    {
        return [
            'nama_kelurahan' => $kelurahan->nama,
            'kecamatan' => $kelurahan->kecamatan ?? '—',
            'kota' => $kelurahan->kota ?? '—',
            'periode' => $this->labelPeriode($filter),
            'tanggal_cetak' => FormatTanggalIndonesia::hariIniLengkap(now()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bagianKeanggotaan(Kelurahan $kelurahan): array
    {
        $jumlahOrang = Orang::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->count();

        $perPokja = [];
        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->orderBy('kode')
            ->get();

        foreach ($pokjaList as $pokja) {
            $perPokja[] = [
                'pokja_kode' => $pokja->kode,
                'pokja_nama' => $pokja->nama,
                'jumlah_aktif' => Keanggotaan::query()
                    ->aktif()
                    ->where('kelurahan_id', $kelurahan->id)
                    ->where('jenis', Keanggotaan::JENIS_TP_PKK)
                    ->where('pokja_id', $pokja->id)
                    ->distinct()
                    ->count('orang_id'),
            ];
        }

        $jumlahKader = Keanggotaan::query()
            ->aktif()
            ->where('kelurahan_id', $kelurahan->id)
            ->whereIn('jenis', [Keanggotaan::JENIS_KADER_UMUM, Keanggotaan::JENIS_KADER_KHUSUS])
            ->distinct()
            ->count('orang_id');

        return [
            'jumlah_orang_terdaftar' => $jumlahOrang,
            'keanggotaan_aktif_per_pokja' => $perPokja,
            'jumlah_kader' => $jumlahKader,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function bagianKegiatan(Kelurahan $kelurahan, array $filter): array
    {
        $query = Kegiatan::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->whereYear('tanggal', $filter['tahun'])
            ->orderBy('tanggal');

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $kegiatanList = $query->withCount([
            'presensi as jumlah_hadir' => fn ($q) => $q->where('hadir', true),
        ])->get();

        $daftar = [];
        $totalHadir = 0;
        foreach ($kegiatanList as $kegiatan) {
            $hadir = (int) $kegiatan->jumlah_hadir;
            $totalHadir += $hadir;
            $daftar[] = [
                'tanggal' => $kegiatan->tanggal?->format('d/m/Y') ?? '—',
                'nama' => $kegiatan->nama,
                'tempat' => $kegiatan->tempat ?: '—',
                'jumlah_hadir' => $hadir,
            ];
        }

        return [
            'jumlah_kegiatan' => count($daftar),
            'daftar' => $daftar,
            'total_peserta_hadir' => $totalHadir,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, int>
     */
    private function bagianAgendaSurat(Kelurahan $kelurahan, array $filter): array
    {
        $masuk = $this->hitungSurat(AgendaSurat::JENIS_MASUK, $kelurahan, $filter);
        $keluar = $this->hitungSurat(AgendaSurat::JENIS_KELUAR, $kelurahan, $filter);

        $disposisiQuery = Disposisi::query()
            ->where('status', '!=', Disposisi::STATUS_SELESAI)
            ->whereHas('agendaSurat', function (Builder $q) use ($kelurahan, $filter): void {
                $q->where('jenis', AgendaSurat::JENIS_MASUK)
                    ->where('kelurahan_id', $kelurahan->id)
                    ->whereNull('pokja_id')
                    ->tahun($filter['tahun']);
                $this->terapkanRentangTanggal($q, 'tanggal_surat', $filter);
            });

        return [
            'surat_masuk' => $masuk,
            'surat_keluar' => $keluar,
            'disposisi_belum_selesai' => $disposisiQuery->count(),
        ];
    }

    private function hitungSurat(string $jenis, Kelurahan $kelurahan, array $filter): int
    {
        $query = AgendaSurat::query()
            ->where('jenis', $jenis)
            ->where('kelurahan_id', $kelurahan->id)
            ->whereNull('pokja_id')
            ->tahun($filter['tahun']);

        $this->terapkanRentangTanggal($query, 'tanggal_surat', $filter);

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function bagianKeuangan(Kelurahan $kelurahan, array $filter): array
    {
        $kas = app(KasService::class)->ringkasanPeriode(
            $kelurahan->id,
            null,
            $filter['tahun'],
            $filter['bulan']
        );

        return [
            'tunai' => $this->formatRingkasanKas($kas['tunai']),
            'bank' => $this->formatRingkasanKas($kas['bank']),
            'total' => $this->formatRingkasanKas($kas['total']),
            'tunai_nilai' => $kas['tunai'],
            'bank_nilai' => $kas['bank'],
            'total_nilai' => $kas['total'],
        ];
    }

    /**
     * @param  array{saldo_awal: float, masuk: float, keluar: float, saldo_akhir: float}  $baris
     * @return array<string, string>
     */
    private function formatRingkasanKas(array $baris): array
    {
        return [
            'saldo_awal' => FormatUang::rupiah($baris['saldo_awal']),
            'masuk' => FormatUang::rupiah($baris['masuk']),
            'keluar' => FormatUang::rupiah($baris['keluar']),
            'saldo_akhir' => FormatUang::rupiah($baris['saldo_akhir']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bagianInventaris(Kelurahan $kelurahan): array
    {
        $items = InventarisBarang::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->whereNull('pokja_id')
            ->get();

        $perKondisi = [
            InventarisBarang::KONDISI_BAIK => 0,
            InventarisBarang::KONDISI_RUSAK_RINGAN => 0,
            InventarisBarang::KONDISI_RUSAK_BERAT => 0,
        ];

        $totalUnit = 0;
        foreach ($items as $barang) {
            $totalUnit += (int) $barang->jumlah;
            $kondisi = $barang->kondisi;
            if (array_key_exists($kondisi, $perKondisi)) {
                $perKondisi[$kondisi] += (int) $barang->jumlah;
            }
        }

        return [
            'jumlah_jenis_barang' => $items->count(),
            'total_unit' => $totalUnit,
            'per_kondisi' => $perKondisi,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function bagianProgramKerja(Kelurahan $kelurahan, array $filter): array
    {
        $query = ProgramKerja::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->whereNull('pokja_id')
            ->tahun($filter['tahun'])
            ->with('kegiatanTertaut');

        $items = $query->get()->filter(fn (ProgramKerja $pk) => $this->programKerjaDalamPeriode($pk, $filter));

        $denganRealisasi = 0;
        $totalKegiatanTertaut = 0;
        foreach ($items as $pk) {
            if ($this->programKerjaPunyaRealisasi($pk)) {
                $denganRealisasi++;
            }
            $totalKegiatanTertaut += $pk->kegiatanTertaut->count();
        }

        return [
            'jumlah_butir' => $items->count(),
            'jumlah_dengan_realisasi' => $denganRealisasi,
            'jumlah_kegiatan_tertaut' => $totalKegiatanTertaut,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function programKerjaDalamPeriode(ProgramKerja $pk, array $filter): bool
    {
        if ($filter['bulan'] === null) {
            return true;
        }

        $bulan = (int) $filter['bulan'];
        if ($pk->bulanRencanaTerpilih($bulan) || $pk->bulanPelaksanaanGabunganTerpilih($bulan)) {
            return true;
        }

        if ($pk->tanggal_kegiatan !== null
            && (int) $pk->tanggal_kegiatan->format('n') === $bulan
            && (int) $pk->tanggal_kegiatan->format('Y') === (int) $filter['tahun']) {
            return true;
        }

        foreach ($pk->kegiatanTertaut as $kegiatan) {
            if ($kegiatan->tanggal !== null
                && (int) $kegiatan->tanggal->format('n') === $bulan
                && (int) $kegiatan->tanggal->format('Y') === (int) $filter['tahun']) {
                return true;
            }
        }

        return false;
    }

    private function programKerjaPunyaRealisasi(ProgramKerja $pk): bool
    {
        if (ProgramKerja::normalisasiBulan($pk->bulan_pelaksanaan) !== []) {
            return true;
        }

        return $pk->kegiatanTertaut->isNotEmpty();
    }

    /**
     * @return array<string, string|null>
     */
    private function bagianStruktur(Kelurahan $kelurahan): array
    {
        $baris = StrukturPengurus::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->where('unit', StrukturPengurus::UNIT_TP_PKK)
            ->urut()
            ->get();

        return [
            'ketua' => $this->cariNamaJabatan($baris, ['ketua']),
            'sekretaris' => $this->cariNamaJabatan($baris, ['sekretaris']),
            'bendahara' => $this->cariNamaJabatan($baris, ['bendahara']),
        ];
    }

    /**
     * @param  Collection<int, StrukturPengurus>  $baris
     * @param  list<string>  $kataKunci
     */
    private function cariNamaJabatan($baris, array $kataKunci): ?string
    {
        foreach ($baris as $item) {
            $jabatan = mb_strtolower((string) $item->jabatan);
            foreach ($kataKunci as $kata) {
                if (str_contains($jabatan, $kata)) {
                    return $item->nama ?: null;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function labelPeriode(array $filter): string
    {
        if ($filter['bulan'] !== null) {
            $nama = FormatTanggalIndonesia::tanggalBulanTahun(
                Carbon::create($filter['tahun'], (int) $filter['bulan'], 1)
            );

            return 'Bulan '.$nama;
        }

        return 'Tahun '.$filter['tahun'];
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filter
     */
    private function terapkanRentangTanggal(Builder $query, string $kolom, array $filter): void
    {
        if ($filter['dari'] !== null) {
            $query->whereDate($kolom, '>=', $filter['dari']);
        }
        if ($filter['sampai'] !== null) {
            $query->whereDate($kolom, '<=', $filter['sampai']);
        }
    }

    private function parseTanggal(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function formatAngka(int $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }
}
