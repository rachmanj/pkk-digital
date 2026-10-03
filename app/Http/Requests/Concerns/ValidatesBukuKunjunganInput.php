<?php

namespace App\Http\Requests\Concerns;

use App\Models\Orang;
use Illuminate\Validation\Rule;

trait ValidatesBukuKunjunganInput
{
    /**
     * @return array<string, mixed>
     */
    protected function bukuKunjunganFieldRules(): array
    {
        $batasTanggal = now()->addDay()->toDateString();

        return [
            'tanggal' => ['required', 'date', 'before_or_equal:'.$batasTanggal],
            'orang_id' => ['nullable', 'integer', Rule::exists(Orang::class, 'id')],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'lokasi_kunjungan' => ['nullable', 'string', 'max:255'],
            'jenis_kegiatan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function bukuKunjunganFieldMessages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh lebih dari satu hari di masa depan.',
            'orang_id.exists' => 'Anggota yang dipilih tidak valid.',
            'nama.required' => 'Nama wajib diisi.',
        ];
    }
}
