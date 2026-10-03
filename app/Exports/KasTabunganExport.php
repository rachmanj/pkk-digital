<?php

namespace App\Exports;

use App\Exports\Concerns\BukuTableExport;

class KasTabunganExport extends BukuTableExport
{
    public function array(): array
    {
        $rows = parent::array();

        $total = (float) ($this->dataset['total_penerimaan'] ?? 0);
        $row = [];
        foreach ($this->dataset['kolom'] as $kolom) {
            if ($kolom['key'] === 'no') {
                $row[] = '';
            } elseif ($kolom['key'] === 'uraian') {
                $row[] = 'JUMLAH';
            } elseif ($kolom['key'] === 'jumlah_penerimaan') {
                $row[] = $this->formatAngka($total);
            } else {
                $row[] = '';
            }
        }
        $rows[] = $row;

        return $rows;
    }

    private function formatAngka(float $nilai): string
    {
        return number_format($nilai, 0, ',', '.');
    }
}
