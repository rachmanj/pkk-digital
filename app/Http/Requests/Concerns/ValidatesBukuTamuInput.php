<?php

namespace App\Http\Requests\Concerns;

use App\Models\Pokja;
use Illuminate\Validation\Rule;

trait ValidatesBukuTamuInput
{
    /**
     * @return array<string, mixed>
     */
    protected function bukuTamuFieldRules(): array
    {
        $batasTanggal = now()->addDay()->toDateString();

        return [
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'tanggal' => ['required', 'date', 'before_or_equal:'.$batasTanggal],
            'nama_tamu' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'keperluan' => ['nullable', 'string', 'max:255'],
            'tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function bukuTamuFieldMessages(): array
    {
        return [
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari satu hari di masa depan.',
            'nama_tamu.required' => 'Nama tamu wajib diisi.',
        ];
    }
}
