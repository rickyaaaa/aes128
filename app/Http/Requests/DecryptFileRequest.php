<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecryptFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'source_file' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    if (strtolower($value->getClientOriginalExtension()) !== 'enc') {
                        $fail('File harus berformat .enc.');
                    }
                },
            ],
            'secret_key' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
