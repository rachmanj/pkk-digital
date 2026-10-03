<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateUbahSandiRequest extends FormRequest
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
            'password_saat_ini' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password_saat_ini.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Kata sandi baru dan konfirmasi tidak sama.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            if ($user === null) {
                return;
            }

            $passwordSaatIni = $this->string('password_saat_ini')->toString();
            if (! Hash::check($passwordSaatIni, $user->password)) {
                $validator->errors()->add('password_saat_ini', 'Kata sandi saat ini tidak benar.');

                return;
            }

            $passwordBaru = $this->string('password')->toString();
            if (Hash::check($passwordBaru, $user->password)) {
                $validator->errors()->add('password', 'Kata sandi baru harus berbeda dari kata sandi saat ini.');
            }
        });
    }
}
