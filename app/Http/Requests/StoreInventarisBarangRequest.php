<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesInventarisBarangInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventarisBarangRequest extends FormRequest
{
    use ValidatesInventarisBarangInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->inventarisBarangFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->inventarisBarangFieldMessages();
    }
}
