<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_private' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'The title must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'is_private.boolean' => 'The privacy setting must be true or false.',
        ];
    }
}
