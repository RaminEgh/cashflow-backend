<?php

namespace App\Http\Requests\Admin\User;

use App\Models\User;
use App\Rules\IranMobileRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'phone' => ['required', 'string', 'min:11', 'max:13', new IranMobileRule],
            'status' => 'required|in:'.implode(',', User::STATUSES),
            'national_code' => 'nullable|string|min:10|max:10',
            'email' => 'nullable|email',
        ];
    }
}
