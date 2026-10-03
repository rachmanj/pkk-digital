<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

abstract class BukuTableExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<string, mixed>  $dataset
     */
    public function __construct(protected array $dataset) {}

    public function headings(): array
    {
        return array_column($this->dataset['kolom'], 'label');
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->dataset['baris'] as $baris) {
            $row = [];
            foreach ($this->dataset['kolom'] as $kolom) {
                $row[] = $baris['cells'][$kolom['key']] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function title(): string
    {
        return (string) $this->dataset['judul'];
    }
}
