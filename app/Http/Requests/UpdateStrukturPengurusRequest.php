<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStrukturPengurusInput;
use App\Support\PkkPermission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStrukturPengurusRequest extends FormRequest
{
    use ValidatesStrukturPengurusInput;

    public function authorize(): bool
    {
        return $this->user()?->can(PkkPermission::KELOLA_STRUKTUR) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->strukturPengurusFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->strukturPengurusFieldMessages();
    }
}
