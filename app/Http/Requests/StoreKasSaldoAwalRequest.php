<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKasSaldoAwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'buku' => ['required', 'string'],
            'saldo_tunai' => ['required', 'numeric', 'gte:0'],
            'saldo_bank' => ['required', 'numeric', 'gte:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tahun.required' => 'Tahun wajib diisi.',
            'saldo_tunai.required' => 'Saldo awal tunai wajib diisi.',
            'saldo_tunai.gte' => 'Saldo awal tunai tidak boleh negatif.',
            'saldo_bank.required' => 'Saldo awal bank wajib diisi.',
            'saldo_bank.gte' => 'Saldo awal bank tidak boleh negatif.',
        ];
    }
}
