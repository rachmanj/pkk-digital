<?php

namespace App\Exports;

use App\Models\InventarisBarang;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanKotaExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $dataset
     */
    public function __construct(protected array $dataset) {}

    public function sheets(): array
    {
        $laporan = $this->dataset['laporan'] ?? [];

        return [
            new LaporanKotaSheetExport('Identitas', $this->barisIdentitas($laporan['identitas'] ?? [])),
            new LaporanKotaSheetExport('Keanggotaan', $this->barisKeanggotaan($laporan['keanggotaan'] ?? [])),
            new LaporanKotaSheetExport('Kegiatan', $this->barisKegiatan($laporan['kegiatan'] ?? [])),
            new LaporanKotaSheetExport('Agenda Surat', $this->barisAgendaSurat($laporan['agenda_surat'] ?? [])),
            new LaporanKotaSheetExport('Keuangan Kas', $this->barisKeuangan($laporan['keuangan'] ?? [])),
            new LaporanKotaSheetExport('Inventaris', $this->barisInventaris($laporan['inventaris'] ?? [])),
            new LaporanKotaSheetExport('Program Kerja', $this->barisProgramKerja($laporan['program_kerja'] ?? [])),
            new LaporanKotaSheetExport('Struktur', $this->barisStruktur($laporan['struktur'] ?? [])),
        ];
    }

    /**
     * @param  array<string, string>  $identitas
     * @return list<list<string>>
     */
    private function barisIdentitas(array $identitas): array
    {
        return [
            ['Nama kelurahan', $identitas['nama_kelurahan'] ?? ''],
            ['Kecamatan', $identitas['kecamatan'] ?? ''],
            ['Kota', $identitas['kota'] ?? ''],
            ['Periode laporan', $identitas['periode'] ?? ''],
            ['Tanggal cetak', $identitas['tanggal_cetak'] ?? ''],
        ];
    }

    /**
     * @param  array<string, mixed>  $keanggotaan
     * @return list<list<string|int>>
     */
    private function barisKeanggotaan(array $keanggotaan): array
    {
        $baris = [
            ['Jumlah orang terdaftar', $keanggotaan['jumlah_orang_terdaftar'] ?? 0],
            ['Jumlah kader', $keanggotaan['jumlah_kader'] ?? 0],
            [],
            ['Pokja', 'Jumlah keanggotaan aktif'],
        ];
        foreach ($keanggotaan['keanggotaan_aktif_per_pokja'] ?? [] as $pokja) {
            $baris[] = [
                'Pokja '.($pokja['pokja_kode'] ?? ''),
                $pokja['jumlah_aktif'] ?? 0,
            ];
        }

        return $baris;
    }

    /**
     * @param  array<string, mixed>  $kegiatan
     * @return list<list<string|int>>
     */
    private function barisKegiatan(array $kegiatan): array
    {
        $baris = [
            ['Jumlah kegiatan', $kegiatan['jumlah_kegiatan'] ?? 0],
            ['Total peserta hadir', $kegiatan['total_peserta_hadir'] ?? 0],
            [],
            ['Tanggal', 'Nama kegiatan', 'Tempat', 'Jumlah hadir'],
        ];
        foreach ($kegiatan['daftar'] ?? [] as $item) {
            $baris[] = [
                $item['tanggal'] ?? '',
                $item['nama'] ?? '',
                $item['tempat'] ?? '',
                $item['jumlah_hadir'] ?? 0,
            ];
        }

        return $baris;
    }

    /**
     * @param  array<string, int>  $agenda
     * @return list<list<string|int>>
     */
    private function barisAgendaSurat(array $agenda): array
    {
        return [
            ['Surat masuk', $agenda['surat_masuk'] ?? 0],
            ['Surat keluar', $agenda['surat_keluar'] ?? 0],
            ['Disposisi belum selesai', $agenda['disposisi_belum_selesai'] ?? 0],
        ];
    }

    /**
     * @param  array<string, mixed>  $keuangan
     * @return list<list<string>>
     */
    private function barisKeuangan(array $keuangan): array
    {
        return [
            ['Pos', 'Saldo awal', 'Pemasukan', 'Pengeluaran', 'Saldo akhir'],
            [
                'Tunai',
                $keuangan['tunai']['saldo_awal'] ?? '',
                $keuangan['tunai']['masuk'] ?? '',
                $keuangan['tunai']['keluar'] ?? '',
                $keuangan['tunai']['saldo_akhir'] ?? '',
            ],
            [
                'Bank',
                $keuangan['bank']['saldo_awal'] ?? '',
                $keuangan['bank']['masuk'] ?? '',
                $keuangan['bank']['keluar'] ?? '',
                $keuangan['bank']['saldo_akhir'] ?? '',
            ],
            [
                'Total',
                $keuangan['total']['saldo_awal'] ?? '',
                $keuangan['total']['masuk'] ?? '',
                $keuangan['total']['keluar'] ?? '',
                $keuangan['total']['saldo_akhir'] ?? '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $inventaris
     * @return list<list<string|int>>
     */
    private function barisInventaris(array $inventaris): array
    {
        $perKondisi = $inventaris['per_kondisi'] ?? [];

        return [
            ['Jumlah jenis barang', $inventaris['jumlah_jenis_barang'] ?? 0],
            ['Total unit', $inventaris['total_unit'] ?? 0],
            ['Baik', $perKondisi[InventarisBarang::KONDISI_BAIK] ?? 0],
            ['Rusak ringan', $perKondisi[InventarisBarang::KONDISI_RUSAK_RINGAN] ?? 0],
            ['Rusak berat', $perKondisi[InventarisBarang::KONDISI_RUSAK_BERAT] ?? 0],
        ];
    }

    /**
     * @param  array<string, int>  $program
     * @return list<list<string|int>>
     */
    private function barisProgramKerja(array $program): array
    {
        return [
            ['Jumlah butir program kerja', $program['jumlah_butir'] ?? 0],
            ['Sudah punya realisasi', $program['jumlah_dengan_realisasi'] ?? 0],
            ['Jumlah kegiatan tertaut', $program['jumlah_kegiatan_tertaut'] ?? 0],
        ];
    }

    /**
     * @param  array<string, string|null>  $struktur
     * @return list<list<string|null>>
     */
    private function barisStruktur(array $struktur): array
    {
        return [
            ['Ketua', $struktur['ketua'] ?? '—'],
            ['Sekretaris', $struktur['sekretaris'] ?? '—'],
            ['Bendahara', $struktur['bendahara'] ?? '—'],
        ];
    }
}

/**
 * @implements FromArray
 */
class LaporanKotaSheetExport implements FromArray, WithTitle
{
    /**
     * @param  list<list<mixed>>  $rows
     */
    public function __construct(
        protected string $title,
        protected array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->title;
    }
}
