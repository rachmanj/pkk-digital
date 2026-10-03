<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesBukuTamuInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreBukuTamuRequest extends FormRequest
{
    use ValidatesBukuTamuInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->bukuTamuFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->bukuTamuFieldMessages();
    }
}
