<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EncryptFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'source_file' => ['required', 'file', 'mimes:jpg,png,pdf', 'max:20480'],
            'secret_key' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
