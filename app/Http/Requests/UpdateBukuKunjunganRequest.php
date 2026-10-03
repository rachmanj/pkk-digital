<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesBukuKunjunganInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBukuKunjunganRequest extends FormRequest
{
    use ValidatesBukuKunjunganInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->bukuKunjunganFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->bukuKunjunganFieldMessages();
    }
}
