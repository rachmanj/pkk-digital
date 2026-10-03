<?php

namespace App\Services;

class BukuCetakTampilan
{
    /** Lebar kertas F4 (mm); DomPDF memakai nama kertas "folio" (setara, bukan string "F4"). */
    public const KERTAS_LEBAR_MM = 210;

    public const KERTAS_TINGGI_MM = 330;

    public const MARGIN_ATAS_BAWAH_MM = 8;

    public const MARGIN_KIRI_KANAN_MM = 6;

    /**
     * @return array{
     *     orientasi: 'portrait'|'landscape',
     *     kelas_orientasi: string,
     *     lebar_kolom: array<string, string>,
     *     dompdf_kertas: string,
     * }
     */
    public function untukKode(string $kodeBuku): array
    {
        $config = config('buku.'.$kodeBuku);
        $jumlahKolom = is_array($config['kolom'] ?? null) ? count($config['kolom']) : 0;
        $landscape = $config !== null
            && ($config['tipe'] ?? '') === 'tabel'
            && $jumlahKolom > 6;

        return [
            'orientasi' => $landscape ? 'landscape' : 'portrait',
            'kelas_orientasi' => $landscape ? 'cetak-landscape' : 'cetak-portrait',
            'lebar_kolom' => $this->lebarKolomPersen($kodeBuku, $jumlahKolom),
            // DomPDF tidak punya preset "F4"; "folio" (612×936 pt) mendekati F4 210×330 mm.
            'dompdf_kertas' => 'folio',
        ];
    }

    public function landscapeUntukKode(string $kodeBuku): bool
    {
        return $this->untukKode($kodeBuku)['orientasi'] === 'landscape';
    }

    /**
     * @return array<string, string> key kolom => lebar CSS (mis. "8%")
     */
    private function lebarKolomPersen(string $kodeBuku, int $jumlahKolom): array
    {
        $preset = $this->presetLebarKolom()[$kodeBuku] ?? null;
        if ($preset !== null) {
            return $this->normalisasiLebar($preset);
        }

        if ($jumlahKolom === 0) {
            return [];
        }

        $sama = round(100 / $jumlahKolom, 4);
        $config = config('buku.'.$kodeBuku);
        $hasil = [];
        foreach ($config['kolom'] as $kolom) {
            $hasil[$kolom['key']] = $sama.'%';
        }

        return $hasil;
    }

    /**
     * @param  array<string, float|int>  $lebar
     * @return array<string, string>
     */
    private function normalisasiLebar(array $lebar): array
    {
        $total = array_sum($lebar);
        if ($total <= 0) {
            return [];
        }

        $hasil = [];
        foreach ($lebar as $key => $nilai) {
            $hasil[$key] = round(($nilai / $total) * 100, 2).'%';
        }

        return $hasil;
    }

    /**
     * @return array<string, array<string, float|int>>
     */
    private function presetLebarKolom(): array
    {
        return [
            'agenda_surat_masuk' => [
                'no' => 3,
                'tanggal_surat' => 7,
                'tanggal_terima' => 7,
                'no_surat' => 9,
                'dari' => 10,
                'perihal' => 18,
                'lampiran' => 6,
                'diteruskan_kepada' => 14,
                'status_tindak_lanjut' => 10,
            ],
            'agenda_surat_keluar' => [
                'no' => 4,
                'no_surat' => 10,
                'tanggal_surat' => 9,
                'kepada' => 14,
                'perihal' => 22,
                'lampiran' => 8,
                'tembusan' => 13,
            ],
            'daftar_hadir' => [
                'no' => 5,
                'nama' => 18,
                'alamat' => 22,
                'jabatan' => 14,
                'keterangan' => 18,
                'tanda_tangan' => 10,
            ],
            'buku_kegiatan' => [
                'no' => 4,
                'tanggal' => 8,
                'nama' => 14,
                'jabatan' => 10,
                'tempat' => 12,
                'uraian' => 28,
                'tanda_tangan' => 8,
            ],
            'daftar_anggota' => [
                'no' => 3,
                'nama' => 11,
                'jabatan' => 8,
                'jenis_kelamin' => 6,
                'tempat_lahir' => 8,
                'tanggal_lahir' => 7,
                'umur' => 4,
                'status' => 5,
                'alamat' => 13,
                'pendidikan' => 7,
                'pekerjaan' => 8,
                'keterangan' => 14,
            ],
            'daftar_anggota_tp_pkk' => [
                'no' => 3,
                'no_registrasi_tp_pkk' => 7,
                'nama' => 10,
                'jenis_kelamin' => 5,
                'dalam_keanggotaan_tp_pkk' => 6,
                'kader_umum' => 5,
                'kader_khusus' => 5,
                'tanggal_lahir' => 7,
                'umur' => 4,
                'status' => 5,
                'alamat' => 11,
                'pendidikan' => 7,
                'pekerjaan' => 7,
                'keterangan' => 10,
            ],
            'buku_tamu' => [
                'no' => 4,
                'tanggal' => 8,
                'nama_tamu' => 16,
                'alamat' => 18,
                'keperluan' => 16,
                'tujuan' => 14,
                'tanda_tangan' => 10,
            ],
            'kas_pokja' => [
                'no' => 5,
                'tanggal' => 10,
                'uraian_pemasukan' => 28,
                'uraian_pengeluaran' => 28,
                'jumlah' => 12,
            ],
            'kas_tabungan' => [
                'no' => 4,
                'tanggal_bulan_tahun' => 14,
                'sumber_dana' => 12,
                'uraian' => 22,
                'nomor_bukti_kas' => 12,
                'jumlah_penerimaan' => 12,
            ],
            'buku_kunjungan' => [
                'no' => 4,
                'tanggal' => 8,
                'nama' => 12,
                'jabatan' => 10,
                'lokasi_kunjungan' => 16,
                'jenis_kegiatan' => 14,
                'keterangan' => 16,
                'tanda_tangan' => 8,
            ],
        ];
    }
}
