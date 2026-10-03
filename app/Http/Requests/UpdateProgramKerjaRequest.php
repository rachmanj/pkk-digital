<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramKerjaInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramKerjaRequest extends FormRequest
{
    use ValidatesProgramKerjaInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->programKerjaFieldRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->programKerjaFieldMessages();
    }
}
