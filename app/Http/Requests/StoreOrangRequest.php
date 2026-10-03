<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesOrangInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrangRequest extends FormRequest
{
    use ValidatesOrangInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->orangFieldRules(), [
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->orangFieldMessages(), [
            'foto.image' => 'Foto harus berupa berkas gambar.',
            'foto.max' => 'Ukuran foto maksimal 2 megabita.',
        ]);
    }
}
