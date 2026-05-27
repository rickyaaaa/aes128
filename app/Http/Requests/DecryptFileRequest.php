<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DecryptFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'encrypted_file' => ['required', 'file', 'max:20480'],
            'secret_key' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('encrypted_file');

                if ($file && strtolower($file->getClientOriginalExtension()) !== 'enc') {
                    $validator->errors()->add('encrypted_file', 'File dekripsi harus berformat .enc.');
                }
            },
        ];
    }
}
