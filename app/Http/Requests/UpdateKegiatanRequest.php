<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesKegiatanInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKegiatanRequest extends FormRequest
{
    use ValidatesKegiatanInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->kegiatanFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->kegiatanFieldMessages();
    }
}
