<?php

namespace App\Http\Requests\Concerns;

use App\Models\AgendaSurat;
use App\Models\Pokja;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesAgendaSuratInput
{
    /**
     * @return array<string, mixed>
     */
    protected function agendaSuratFieldRules(): array
    {
        return [
            'jenis' => ['required', Rule::in([AgendaSurat::JENIS_MASUK, AgendaSurat::JENIS_KELUAR])],
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_terima' => ['nullable', 'date', 'required_if:jenis,masuk'],
            'no_surat' => ['required', 'string', 'max:255'],
            'dari' => ['nullable', 'string', 'max:255'],
            'kepada' => ['nullable', 'string', 'max:255', 'required_if:jenis,keluar'],
            'perihal' => ['required', 'string', 'max:255'],
            'lampiran_keterangan' => ['nullable', 'string', 'max:255'],
            'tembusan' => ['nullable', 'string', 'max:255'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:5120'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function agendaSuratFieldMessages(): array
    {
        return [
            'jenis.required' => 'Jenis surat wajib diisi.',
            'jenis.in' => 'Jenis surat harus masuk atau keluar.',
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
            'tanggal_surat.date' => 'Tanggal surat harus berupa tanggal yang valid.',
            'tanggal_terima.required_if' => 'Tanggal terima wajib diisi untuk surat masuk.',
            'tanggal_terima.date' => 'Tanggal terima harus berupa tanggal yang valid.',
            'no_surat.required' => 'Nomor surat wajib diisi.',
            'perihal.required' => 'Perihal wajib diisi.',
            'kepada.required_if' => 'Kepada wajib diisi untuk surat keluar.',
            'lampiran.file' => 'Lampiran harus berupa berkas.',
            'lampiran.mimes' => 'Lampiran harus berupa berkas PDF atau gambar.',
            'lampiran.max' => 'Ukuran lampiran maksimal 5 megabita.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pokjaId = $this->input('pokja_id');
            if ($pokjaId === null || $pokjaId === '') {
                return;
            }

            $exists = Pokja::query()->whereKey((int) $pokjaId)->exists();
            if (! $exists) {
                $validator->errors()->add('pokja_id', 'Pokja yang dipilih tidak valid.');
            }
        });
    }
}
