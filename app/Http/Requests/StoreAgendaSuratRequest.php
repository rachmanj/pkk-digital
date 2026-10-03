<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAgendaSuratInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgendaSuratRequest extends FormRequest
{
    use ValidatesAgendaSuratInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->agendaSuratFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->agendaSuratFieldMessages();
    }
}
