<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class NotulenExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<string, mixed>  $dataset
     */
    public function __construct(protected array $dataset) {}

    public function headings(): array
    {
        return ['Bidang', 'Isi'];
    }

    public function array(): array
    {
        $notulen = $this->dataset['notulen'];
        $kegiatan = $this->dataset['kegiatan'];

        if ($kegiatan === null) {
            return [];
        }

        $baris = [
            ['Nama kegiatan', $kegiatan->nama],
            ['Tanggal', $kegiatan->tanggal?->format('d/m/Y') ?? '—'],
            ['Tempat', $kegiatan->tempat ?: '—'],
            ['Macam rapat', $notulen?->macam_rapat ?: '—'],
            ['Jumlah diundang', $notulen?->jumlah_diundang !== null ? (string) $notulen->jumlah_diundang : '—'],
            ['Jumlah hadir', $notulen?->jumlah_hadir !== null ? (string) $notulen->jumlah_hadir : '—'],
            ['Jumlah tidak hadir', $notulen?->jumlah_tidak_hadir !== null ? (string) $notulen->jumlah_tidak_hadir : '—'],
            ['Uraian jalannya rapat', $notulen?->uraian_jalannya ?: '—'],
            ['Keputusan', $notulen?->keputusan ?: '—'],
            ['Lain-lain', $notulen?->lain_lain ?: '—'],
            ['Penutup', $notulen?->penutup ?: '—'],
            ['Tempat dan tanggal tanda tangan', $notulen?->tempat_tanggal_ttd ?: '—'],
        ];

        return $baris;
    }

    public function title(): string
    {
        return (string) $this->dataset['judul'];
    }
}
