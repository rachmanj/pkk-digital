<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKasTutupBukuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buku' => ['required', 'string', 'max:64'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'tanggal_tutup' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'nama_ketua' => ['nullable', 'string', 'max:255'],
            'nama_bendahara' => ['nullable', 'string', 'max:255'],
        ];
    }
}
