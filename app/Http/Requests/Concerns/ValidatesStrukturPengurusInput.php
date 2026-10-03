<?php

namespace App\Http\Requests\Concerns;

use App\Models\StrukturPengurus;
use Illuminate\Validation\Rule;

trait ValidatesStrukturPengurusInput
{
    /**
     * @return array<string, mixed>
     */
    protected function strukturPengurusFieldRules(): array
    {
        return [
            'unit' => ['required', 'string', Rule::in(StrukturPengurus::unitNilai())],
            'pokja_id' => ['nullable', 'integer', 'exists:pokja,id'],
            'rt' => ['nullable', 'string', 'max:20'],
            'jabatan' => ['required', 'string', 'max:120'],
            'nama' => ['required', 'string', 'max:200'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function strukturPengurusFieldMessages(): array
    {
        return [
            'unit.required' => 'Unit wajib dipilih.',
            'unit.in' => 'Unit tidak valid.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'nama.required' => 'Nama wajib diisi.',
        ];
    }
}
