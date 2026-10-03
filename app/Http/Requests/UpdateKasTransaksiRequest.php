<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesKasTransaksiInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKasTransaksiRequest extends FormRequest
{
    use ValidatesKasTransaksiInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->kasTransaksiFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->kasTransaksiFieldMessages();
    }
}
