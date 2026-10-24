<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MassDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmation' => strtolower(trim((string) $this->input('confirmation'))),
        ]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:hapus'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Ketik kata "hapus" untuk melanjutkan.',
        ];
    }
}
