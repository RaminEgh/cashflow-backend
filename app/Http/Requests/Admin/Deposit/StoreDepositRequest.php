<?php

namespace App\Http\Requests\Admin\Deposit;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
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
            'branch_name' => 'required|string|min:3',
            'branch_code' => 'required|string|min:3',
            'number' => 'required|string|min:3',
            'sheba' => 'required|string|min:3',
            'currency' => 'required|string|min:3',
            'type' => 'required|integer|in:1,2,3,4,5,6,7',
            'description' => 'required|string|min:3',
            'bank_id' => 'required|integer|exists:users,id',
            'organ_id' => 'required|integer|exists:organs,id',
        ];
    }
}
