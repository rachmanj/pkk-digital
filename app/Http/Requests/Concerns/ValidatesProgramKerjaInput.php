<?php

namespace App\Http\Requests\Concerns;

use App\Models\Pokja;
use App\Models\ProgramKerja;
use Illuminate\Validation\Rule;

trait ValidatesProgramKerjaInput
{
    /**
     * @return array<string, mixed>
     */
    protected function programKerjaFieldRules(): array
    {
        return [
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kode' => ['nullable', 'string', 'max:16'],
            'program' => ['nullable', 'string', 'max:255'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'tanggal_kegiatan' => ['nullable', 'date'],
            'tujuan' => ['nullable', 'string'],
            'sasaran' => ['nullable', 'string'],
            'tempat' => ['nullable', 'string', 'max:255'],
            'sumber_dana' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'bulan_rencana' => ['nullable', 'array'],
            'bulan_rencana.*' => ['integer', Rule::in(ProgramKerja::daftarBulan())],
            'bulan_pelaksanaan' => ['nullable', 'array'],
            'bulan_pelaksanaan.*' => ['integer', Rule::in(ProgramKerja::daftarBulan())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function programKerjaFieldMessages(): array
    {
        return [
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'kegiatan.required' => 'Kegiatan wajib diisi.',
            'bulan_rencana.*.in' => 'Bulan perencanaan tidak valid.',
            'bulan_pelaksanaan.*.in' => 'Bulan pelaksanaan tidak valid.',
        ];
    }
}
