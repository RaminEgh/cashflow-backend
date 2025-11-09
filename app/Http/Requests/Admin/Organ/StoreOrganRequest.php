<?php

namespace App\Http\Requests\Admin\Organ;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganRequest extends FormRequest
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
            'name' => 'required|string|min:3',
            'en_name' => 'required|string|min:3',
            'description' => 'string|min:3',
            'phone' => 'required|string|min:4',
            'admins_id' => 'array',
            'admins_id.*' => 'integer|exists:users,id',
            //            'background' => 'required|string|min:8',
            //            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
