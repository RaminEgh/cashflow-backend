<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
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
            'first_name' => 'required|min:3|max:64',
            'last_name' => 'required|min:3|max:64',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|max:64',
            'roles' => 'required|array',
            'roles.*' => 'required|integer|exists:roles,id',
        ];
    }
}
