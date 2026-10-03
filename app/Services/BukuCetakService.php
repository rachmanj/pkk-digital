<?php

namespace App\Services;

use App\Models\AgendaSurat;
use App\Models\BukuKunjungan;
use App\Models\BukuTamu;
use App\Models\InventarisBarang;
use App\Models\KasTransaksi;
use App\Models\Keanggotaan;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Notulen;
use App\Models\Orang;
use App\Models\Presensi;
use App\Support\FormatTanggalIndonesia;
use App\Support\FormatUang;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class BukuCetakService
{
    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    public function data(string $kodeBuku, array $filter = []): array
    {
        $config = config('buku.'.$kodeBuku);
        if ($config === null) {
            abort(404);
        }

        $filter = $this->normalisasiFilter($filter);
        $kelurahan = Kelurahan::query()->where('is_active', true)->first();

        $payload = [
            'kode' => $kodeBuku,
            'judul' => $config['judul'],
            'tipe' => $config['tipe'],
            'tahun' => $filter['tahun'],
            'kolom' => $config['kolom'],
            'tanda_tangan' => $config['tanda_tangan'] ?? [],
            'baris' => [],
            'notulen' => null,
            'kegiatan' => null,
        ];

        if ($config['tipe'] === 'notulen') {
            $hasil = $this->muatNotulen($filter, $kelurahan);
            $payload['notulen'] = $hasil['notulen'];
            $payload['kegiatan'] = $hasil['kegiatan'];
            $payload['tahun'] = $hasil['kegiatan']?->tanggal?->year ?? $filter['tahun'];

            return $payload;
        }

        $payload['baris'] = match ($kodeBuku) {
            'agenda_surat_masuk' => $this->barisAgendaSurat(AgendaSurat::JENIS_MASUK, $filter, $kelurahan),
            'agenda_surat_keluar' => $this->barisAgendaSurat(AgendaSurat::JENIS_KELUAR, $filter, $kelurahan),
            'daftar_hadir' => $this->barisDaftarHadir($filter, $kelurahan),
            'buku_kegiatan' => $this->barisBukuKegiatan($filter, $kelurahan),
            'daftar_anggota' => $this->barisDaftarAnggota($filter, $kelurahan),
            'daftar_anggota_tp_pkk' => $this->barisDaftarAnggotaTpPkk($filter, $kelurahan),
            'buku_tamu' => $this->barisBukuTamu($filter, $kelurahan),
            'buku_kunjungan' => $this->barisBukuKunjungan($filter, $kelurahan),
            'buku_inventaris' => $this->barisBukuInventaris($filter, $kelurahan),
            'kas_pokja' => $this->barisKasPokja($filter, $kelurahan),
            'kas_tabungan' => $this->barisKasTabungan($filter, $kelurahan),
            default => [],
        };

        if ($kodeBuku === 'kas_tabungan') {
            $payload['total_penerimaan'] = $this->totalPenerimaanKasTabungan($filter, $kelurahan);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array<string, mixed>
     */
    private function normalisasiFilter(array $filter): array
    {
        $tahun = (int) ($filter['tahun'] ?? now()->year);
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) now()->year;
        }

        $pokjaId = isset($filter['pokja_id']) && $filter['pokja_id'] !== ''
            ? (int) $filter['pokja_id']
            : null;

        $buku = (string) ($filter['buku'] ?? '');
        if ($pokjaId === null && str_starts_with($buku, 'pokja-')) {
            $pokjaId = (int) substr($buku, 6);
        }

        $kegiatanId = isset($filter['kegiatan']) && $filter['kegiatan'] !== ''
            ? (int) $filter['kegiatan']
            : null;

        $tutupBukuId = isset($filter['tutup_buku']) && $filter['tutup_buku'] !== ''
            ? (int) $filter['tutup_buku']
            : null;

        return [
            'tahun' => $tahun,
            'dari' => $this->parseTanggal($filter['dari'] ?? null),
            'sampai' => $this->parseTanggal($filter['sampai'] ?? null),
            'pokja_id' => $pokjaId,
            'kegiatan' => $kegiatanId,
            'buku' => $buku,
            'tutup_buku' => $tutupBukuId,
        ];
    }

    private function parseTanggal(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatTanggal(?Carbon $tanggal): string
    {
        return $tanggal?->format('d/m/Y') ?? '—';
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisAgendaSurat(string $jenis, array $filter, ?Kelurahan $kelurahan): array
    {
        $query = AgendaSurat::query()
            ->with(['disposisi.pokja', 'disposisi.user'])
            ->where('jenis', $jenis)
            ->tahun($filter['tahun'])
            ->orderBy('no_urut_tahun');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($filter['pokja_id']) {
            $query->where('pokja_id', $filter['pokja_id']);
        } else {
            $query->whereNull('pokja_id');
        }

        $this->terapkanRentangTanggal($query, 'tanggal_surat', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $surat) {
            if ($jenis === AgendaSurat::JENIS_MASUK) {
                $cells = [
                    'no' => (string) $no,
                    'tanggal_surat' => $this->formatTanggal($surat->tanggal_surat),
                    'tanggal_terima' => $this->formatTanggal($surat->tanggal_terima),
                    'no_surat' => (string) $surat->no_surat,
                    'dari' => $surat->dari ?: '—',
                    'perihal' => (string) $surat->perihal,
                    'lampiran' => $surat->lampiran ?: ($surat->file_path ? '1 berkas' : '—'),
                    'diteruskan_kepada' => $surat->diteruskanKepadaRingkas(),
                    'status_tindak_lanjut' => $this->labelStatusTindakLanjut($surat->statusTindakLanjut()),
                ];
            } else {
                $cells = [
                    'no' => (string) $no,
                    'no_surat' => (string) $surat->no_surat,
                    'tanggal_surat' => $this->formatTanggal($surat->tanggal_surat),
                    'kepada' => $surat->kepada ?: '—',
                    'perihal' => (string) $surat->perihal,
                    'lampiran' => $surat->lampiran ?: ($surat->file_path ? '1 berkas' : '—'),
                    'tembusan' => $surat->tembusan ?: '—',
                ];
            }
            $baris[] = ['cells' => $cells];
            $no++;
        }

        return $baris;
    }

    private function labelStatusTindakLanjut(string $status): string
    {
        return match ($status) {
            'belum' => 'Belum disposisi',
            'baru' => 'Baru',
            'proses' => 'Proses',
            'selesai' => 'Selesai',
            default => $status,
        };
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisDaftarHadir(array $filter, ?Kelurahan $kelurahan): array
    {
        if ($filter['kegiatan'] === null) {
            return [];
        }

        $kegiatan = Kegiatan::query()
            ->with(['presensi.orang.keanggotaan'])
            ->when($kelurahan, fn ($q) => $q->where('kelurahan_id', $kelurahan->id))
            ->find($filter['kegiatan']);

        if ($kegiatan === null) {
            return [];
        }

        $baris = [];
        $no = 1;
        foreach ($kegiatan->presensi->where('hadir', true)->sortBy('urut') as $presensi) {
            $baris[] = ['cells' => $this->selPresensi($presensi, $no)];
            $no++;
        }

        return $baris;
    }

    /**
     * @return array<string, string>
     */
    private function selPresensi(Presensi $presensi, int $no): array
    {
        $presensi->loadMissing('orang.keanggotaan');
        $nama = $presensi->nama_tampil;
        $alamat = '—';
        $jabatan = $presensi->jabatan_manual ?: '—';

        if ($presensi->orang !== null) {
            $alamat = $presensi->orang->alamat ?: '—';
            if ($jabatan === '—') {
                $aktif = $presensi->orang->keanggotaan->firstWhere('is_aktif', true)
                    ?? $presensi->orang->keanggotaan->first();
                $jabatan = $aktif?->jabatan ?: '—';
            }
        } elseif ($presensi->alamat_manual) {
            $alamat = $presensi->alamat_manual;
        }

        return [
            'no' => (string) $no,
            'nama' => $nama !== '' ? $nama : '—',
            'alamat' => $alamat,
            'jabatan' => $jabatan,
            'keterangan' => $presensi->keterangan ?: '—',
            'tanda_tangan' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisBukuKegiatan(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = Kegiatan::query()
            ->with(['pimpinanRapat.keanggotaan'])
            ->tahun($filter['tahun'])
            ->orderBy('tanggal')
            ->orderBy('nama');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($filter['pokja_id']) {
            $query->where('pokja_id', $filter['pokja_id']);
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $kegiatan) {
            $pimpinan = $kegiatan->pimpinanRapat;
            $jabatan = '—';
            if ($pimpinan !== null) {
                $aktif = $pimpinan->keanggotaan->firstWhere('is_aktif', true)
                    ?? $pimpinan->keanggotaan->first();
                $jabatan = $aktif?->jabatan ?: '—';
            }

            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'tanggal' => $this->formatTanggal($kegiatan->tanggal),
                    'nama' => $pimpinan?->nama ?? $kegiatan->nama,
                    'jabatan' => $jabatan,
                    'tempat' => $kegiatan->tempat ?: '—',
                    'uraian' => $kegiatan->uraian ?: ($kegiatan->acara ?: '—'),
                    'tanda_tangan' => '',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return array{notulen: ?Notulen, kegiatan: ?Kegiatan}
     */
    private function muatNotulen(array $filter, ?Kelurahan $kelurahan): array
    {
        if ($filter['kegiatan'] === null) {
            return ['notulen' => null, 'kegiatan' => null];
        }

        $kegiatan = Kegiatan::query()
            ->with(['notulen.pembuat', 'pimpinanRapat', 'pokja'])
            ->when($kelurahan, fn ($q) => $q->where('kelurahan_id', $kelurahan->id))
            ->find($filter['kegiatan']);

        if ($kegiatan === null) {
            return ['notulen' => null, 'kegiatan' => null];
        }

        return [
            'notulen' => $kegiatan->notulen,
            'kegiatan' => $kegiatan,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisDaftarAnggota(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = Orang::query()
            ->with(['keanggotaan' => fn ($q) => $q->orderByDesc('is_aktif')])
            ->orderBy('nama');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($filter['pokja_id']) {
            $query->whereHas('keanggotaan', fn ($q) => $q->where('pokja_id', $filter['pokja_id']));
        }

        $baris = [];
        $no = 1;
        foreach ($query->get() as $orang) {
            $keanggotaanAktif = $orang->keanggotaan->firstWhere('is_aktif', true)
                ?? $orang->keanggotaan->first();
            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'nama' => $orang->nama,
                    'jabatan' => $keanggotaanAktif?->jabatan ?: '—',
                    'jenis_kelamin' => $orang->jenis_kelamin ?: '—',
                    'tempat_lahir' => $orang->tempat_lahir ?: '—',
                    'tanggal_lahir' => $this->formatTanggal($orang->tanggal_lahir),
                    'umur' => $orang->umur !== null ? (string) $orang->umur : '—',
                    'status' => $orang->status_perkawinan ?: '—',
                    'alamat' => $orang->alamat ?: '—',
                    'pendidikan' => $orang->pendidikan ?: '—',
                    'pekerjaan' => $orang->pekerjaan ?: '—',
                    'keterangan' => $orang->catatan ?: '—',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisDaftarAnggotaTpPkk(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = Orang::query()
            ->with(['keanggotaan' => fn ($q) => $q->orderByDesc('is_aktif')])
            ->orderBy('nama');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $baris = [];
        $no = 1;
        foreach ($query->get() as $orang) {
            $aktif = $orang->keanggotaan->where('is_aktif', true);
            $noReg = $orang->keanggotaan
                ->first(fn (Keanggotaan $k) => $k->no_registrasi !== null)
                ?->no_registrasi;

            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'no_registrasi_tp_pkk' => $noReg ?: '—',
                    'nama' => $orang->nama,
                    'jenis_kelamin' => $orang->jenis_kelamin ?: '—',
                    'dalam_keanggotaan_tp_pkk' => $aktif->contains('jenis', Keanggotaan::JENIS_TP_PKK) ? '✓' : '—',
                    'kader_umum' => $aktif->contains('jenis', Keanggotaan::JENIS_KADER_UMUM) ? '✓' : '—',
                    'kader_khusus' => $aktif->contains('jenis', Keanggotaan::JENIS_KADER_KHUSUS) ? '✓' : '—',
                    'tanggal_lahir' => $this->formatTanggal($orang->tanggal_lahir),
                    'umur' => $orang->umur !== null ? (string) $orang->umur : '—',
                    'status' => $orang->status_perkawinan ?: '—',
                    'alamat' => $orang->alamat ?: '—',
                    'pendidikan' => $orang->pendidikan ?: '—',
                    'pekerjaan' => $orang->pekerjaan ?: '—',
                    'keterangan' => $orang->catatan ?: '—',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisBukuTamu(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = BukuTamu::query()
            ->tahun($filter['tahun'])
            ->orderBy('no_urut_tahun');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($filter['pokja_id']) {
            $query->where('pokja_id', $filter['pokja_id']);
        } else {
            $query->whereNull('pokja_id');
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $tamu) {
            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'tanggal' => $this->formatTanggal($tamu->tanggal),
                    'nama_tamu' => $tamu->nama_tamu,
                    'alamat' => $tamu->alamat ?: '—',
                    'keperluan' => $tamu->keperluan ?: '—',
                    'tujuan' => $tamu->tujuan ?: '—',
                    'tanda_tangan' => '',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisBukuInventaris(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = InventarisBarang::query()
            ->tahun($filter['tahun'])
            ->orderBy('nama_barang')
            ->orderBy('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($filter['pokja_id']) {
            $query->where('pokja_id', $filter['pokja_id']);
        } else {
            $query->whereNull('pokja_id');
        }

        $baris = [];
        $no = 1;
        foreach ($query->get() as $item) {
            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'nama_barang' => $item->nama_barang,
                    'asal_barang' => $item->asal_barang ?: '—',
                    'tanggal_terima' => $this->formatTanggal($item->tanggal_terima),
                    'jumlah' => number_format($item->jumlah, 0, ',', '.'),
                    'tempat_penyimpanan' => $item->tempat_penyimpanan ?: '—',
                    'kondisi' => InventarisBarang::labelKondisiUntuk($item->kondisi),
                    'keterangan' => $item->keterangan ?: '—',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisBukuKunjungan(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = BukuKunjungan::query()
            ->tahun($filter['tahun'])
            ->orderBy('no_urut_tahun');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $kunjungan) {
            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'tanggal' => $this->formatTanggal($kunjungan->tanggal),
                    'nama' => $kunjungan->nama,
                    'jabatan' => $kunjungan->jabatan ?: '—',
                    'lokasi_kunjungan' => $kunjungan->lokasi_kunjungan,
                    'jenis_kegiatan' => $kunjungan->jenis_kegiatan,
                    'keterangan' => $kunjungan->keterangan ?: '—',
                    'tanda_tangan' => '',
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filter
     */
    private function terapkanRentangTanggal($query, string $kolom, array $filter): void
    {
        if ($filter['dari'] !== null) {
            $query->whereDate($kolom, '>=', $filter['dari']);
        }
        if ($filter['sampai'] !== null) {
            $query->whereDate($kolom, '<=', $filter['sampai']);
        }
    }

    public static function kodeTerdaftar(string $kode): bool
    {
        return Arr::has(config('buku'), $kode);
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisKasPokja(array $filter, ?Kelurahan $kelurahan): array
    {
        if ($filter['pokja_id'] === null) {
            return [];
        }

        $query = KasTransaksi::query()
            ->tahun($filter['tahun'])
            ->where('pokja_id', $filter['pokja_id'])
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $transaksi) {
            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'tanggal' => $this->formatTanggal($transaksi->tanggal),
                    'uraian_pemasukan' => $transaksi->jenis === KasTransaksi::JENIS_MASUK
                        ? (string) $transaksi->uraian
                        : '—',
                    'uraian_pengeluaran' => $transaksi->jenis === KasTransaksi::JENIS_KELUAR
                        ? (string) $transaksi->uraian
                        : '—',
                    'jumlah' => FormatUang::rupiah($transaksi->jumlah),
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return list<array{cells: array<string, string>}>
     */
    private function barisKasTabungan(array $filter, ?Kelurahan $kelurahan): array
    {
        $query = KasTransaksi::query()
            ->tahun($filter['tahun'])
            ->whereNull('pokja_id')
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        $baris = [];
        $no = 1;
        foreach ($query->get() as $transaksi) {
            $nominal = (float) $transaksi->jumlah;
            if ($transaksi->jenis === KasTransaksi::JENIS_KELUAR) {
                $nominal = -$nominal;
            }

            $baris[] = [
                'cells' => [
                    'no' => (string) $no,
                    'tanggal_bulan_tahun' => FormatTanggalIndonesia::tanggalBulanTahun($transaksi->tanggal),
                    'sumber_dana' => $transaksi->jenis === KasTransaksi::JENIS_MASUK
                        ? ($transaksi->sumber_dana ?: '—')
                        : '—',
                    'uraian' => (string) $transaksi->uraian,
                    'nomor_bukti_kas' => $transaksi->no_bukti ?: '—',
                    'jumlah_penerimaan' => $this->formatAngkaKasTabungan($nominal),
                ],
            ];
            $no++;
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function totalPenerimaanKasTabungan(array $filter, ?Kelurahan $kelurahan): float
    {
        $query = KasTransaksi::query()
            ->tahun($filter['tahun'])
            ->whereNull('pokja_id')
            ->where('jenis', KasTransaksi::JENIS_MASUK);

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $this->terapkanRentangTanggal($query, 'tanggal', $filter);

        return (float) $query->sum('jumlah');
    }

    private function formatAngkaKasTabungan(float $nilai): string
    {
        $formatted = number_format(abs($nilai), 0, ',', '.');
        if ($nilai < 0) {
            return '-'.$formatted;
        }

        return $formatted;
    }
}
