<?php

namespace App\Http\Requests\Concerns;

use App\Models\Kegiatan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\Rt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesKegiatanInput
{
    /**
     * @return array<string, mixed>
     */
    protected function kegiatanFieldRules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(Kegiatan::daftarJenis())],
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'rt_id' => ['nullable', 'integer', Rule::exists(Rt::class, 'id')],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'tempat' => ['required', 'string', 'max:255'],
            'acara' => ['required', 'string', 'max:255'],
            'uraian' => ['nullable', 'string'],
            'pimpinan_rapat_id' => ['nullable', 'integer', Rule::exists(Orang::class, 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function kegiatanFieldMessages(): array
    {
        return [
            'nama.required' => 'Nama kegiatan wajib diisi.',
            'jenis.required' => 'Jenis kegiatan wajib diisi.',
            'jenis.in' => 'Jenis kegiatan harus salah satu dari daftar yang tersedia.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tempat.required' => 'Tempat wajib diisi.',
            'acara.required' => 'Acara wajib diisi.',
            'pimpinan_rapat_id.exists' => 'Pimpinan rapat yang dipilih tidak valid.',
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'rt_id.exists' => 'RT yang dipilih tidak valid.',
            'jam_mulai.date_format' => 'Jam mulai harus berformat jam yang valid.',
            'jam_selesai.date_format' => 'Jam selesai harus berformat jam yang valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $jamMulai = $this->input('jam_mulai');
            $jamSelesai = $this->input('jam_selesai');
            if ($jamMulai !== null && $jamMulai !== '' && $jamSelesai !== null && $jamSelesai !== '') {
                if ($jamSelesai < $jamMulai) {
                    $validator->errors()->add('jam_selesai', 'Jam selesai tidak boleh lebih awal dari jam mulai.');
                }
            }

            $pimpinanId = $this->input('pimpinan_rapat_id');
            if ($pimpinanId !== null && $pimpinanId !== '') {
                $exists = Orang::query()->whereKey((int) $pimpinanId)->exists();
                if (! $exists) {
                    $validator->errors()->add('pimpinan_rapat_id', 'Pimpinan rapat yang dipilih tidak valid.');
                }
            }
        });
    }
}
