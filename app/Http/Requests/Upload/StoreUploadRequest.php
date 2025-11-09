<?php

namespace App\Http\Requests\Upload;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:' . config('upload.max_file_size', 10240),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_private' => ['nullable', 'boolean'],
            'allowed_types' => ['nullable', 'array'],
            'allowed_types.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed :max KB.',
            'title.max' => 'The title must not exceed 255 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
            'is_private.boolean' => 'The privacy setting must be true or false.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $allowedTypes = $this->input('allowed_types', []);

                if (! empty($allowedTypes)) {
                    $mimeType = $file->getMimeType();
                    $extension = $file->getClientOriginalExtension();

                    $isAllowed = false;
                    foreach ($allowedTypes as $type) {
                        if (str_starts_with($mimeType, $type) || $extension === $type) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if (! $isAllowed) {
                        $validator->errors()->add('file', 'The file type is not allowed. Allowed types: ' . implode(', ', $allowedTypes));
                    }
                }
            }
        });
    }
}
