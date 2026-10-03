<?php

namespace App\Http\Requests\Concerns;

use App\Models\Keanggotaan;
use Illuminate\Validation\Validator;

trait ValidatesOrangInput
{
    /**
     * @return array<string, mixed>
     */
    protected function orangFieldRules(bool $requireKeanggotaan = true): array
    {
        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'status_perkawinan' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'rt_id' => ['nullable', 'integer', 'exists:rt,id'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string'],
            'keanggotaan' => [$requireKeanggotaan ? 'required' : 'nullable', 'array', 'min:1'],
            'keanggotaan.*.id' => ['nullable', 'integer', 'exists:keanggotaan,id'],
            'keanggotaan.*.jenis' => ['required', 'string', 'in:'.implode(',', Keanggotaan::JENIS_VALUES)],
            'keanggotaan.*.pokja_id' => ['nullable', 'integer', 'exists:pokja,id'],
            'keanggotaan.*.jabatan' => ['nullable', 'string', 'max:255'],
            'keanggotaan.*.no_registrasi' => ['nullable', 'string', 'max:255'],
            'keanggotaan.*.sk_nomor' => ['nullable', 'string', 'max:255'],
            'keanggotaan.*.mulai' => ['nullable', 'date'],
            'keanggotaan.*.selesai' => ['nullable', 'date', 'after_or_equal:keanggotaan.*.mulai'],
            'keanggotaan.*.is_aktif' => ['nullable', 'boolean'],
        ];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function orangFieldMessages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            'tanggal_lahir.date' => 'Tanggal lahir harus berupa tanggal yang valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'rt_id.exists' => 'RT yang dipilih tidak valid.',
            'keanggotaan.required' => 'Keanggotaan wajib diisi.',
            'keanggotaan.min' => 'Minimal satu keanggotaan harus diisi.',
            'keanggotaan.*.jenis.required' => 'Jenis keanggotaan wajib diisi.',
            'keanggotaan.*.jenis.in' => 'Jenis keanggotaan harus TP PKK, Kader Umum, atau Kader Khusus.',
            'keanggotaan.*.pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('keanggotaan', []);

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $jenis = $item['jenis'] ?? null;
                $jabatan = $item['jabatan'] ?? '';
                $pokjaId = $item['pokja_id'] ?? null;

                if ($jenis === Keanggotaan::JENIS_TP_PKK
                    && is_string($jabatan)
                    && stripos($jabatan, 'Pokja') !== false
                    && empty($pokjaId)) {
                    $validator->errors()->add(
                        "keanggotaan.{$index}.pokja_id",
                        'Pokja wajib dipilih untuk jabatan yang mencantumkan Pokja.'
                    );
                }
            }
        });
    }
}
