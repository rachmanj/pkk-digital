<?php

namespace App\Http\Requests;

use App\Models\Pokja;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDisposisiRequest extends FormRequest
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
            'pokja_id' => ['nullable', 'integer', Rule::exists(Pokja::class, 'id')],
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
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
            'pokja_id.exists' => 'Pokja yang dipilih tidak valid.',
            'user_id.exists' => 'Pengguna yang dipilih tidak valid.',
            'tenggat.date' => 'Tenggat harus berupa tanggal yang valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $pokjaId = $this->input('pokja_id');
            $userId = $this->input('user_id');

            $hasPokja = $pokjaId !== null && $pokjaId !== '';
            $hasUser = $userId !== null && $userId !== '';

            if (! $hasPokja && ! $hasUser) {
                $validator->errors()->add('pokja_id', 'Pilih Pokja atau pengguna penerima disposisi.');
            }
        });
    }
}
