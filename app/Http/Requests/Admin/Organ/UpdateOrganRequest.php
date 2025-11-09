<?php

namespace App\Http\Requests\Admin\Organ;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|min:3',
            'en_name' => 'string|min:3',
            'description' => 'string|min:3',
            'phone' => 'string|min:4',
            'admins_id' => 'array',
            'admins_id.*' => 'integer|exists:users,id',
            'logo' => 'string|exists:uploads,slug',
            'background' => 'string|exists:uploads,slug',
        ];
    }
}
