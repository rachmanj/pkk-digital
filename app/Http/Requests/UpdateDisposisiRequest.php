<?php

namespace App\Http\Requests;

use App\Models\Disposisi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDisposisiRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                Disposisi::STATUS_BARU,
                Disposisi::STATUS_PROSES,
                Disposisi::STATUS_SELESAI,
            ])],
            'instruksi' => ['nullable', 'string'],
            'tenggat' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status disposisi wajib diisi.',
            'status.in' => 'Status disposisi tidak valid.',
            'tenggat.date' => 'Tenggat harus berupa tanggal yang valid.',
        ];
    }
}
