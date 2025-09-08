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
            'name' => 'required|string|min:3',
            'en_name' => 'required|string|min:3',
            'description' => 'nullable|string|min:3',
            'phone' => 'nullable|string|min:4',
            'admins_id' => 'nullable|array',
            'admins_id.*' => 'required|integer|exists:users,id',
            'year' => 'integer|min:1404|max:2050',
            'allocation_description' => 'string|min:3',
            'month_1_budget' => 'integer|min:0',
            'month_2_budget' => 'integer|min:0',
            'month_3_budget' => 'integer|min:0',
            'month_4_budget' => 'integer|min:0',
            'month_5_budget' => 'integer|min:0',
            'month_6_budget' => 'integer|min:0',
            'month_7_budget' => 'integer|min:0',
            'month_8_budget' => 'integer|min:0',
            'month_9_budget' => 'integer|min:0',
            'month_10_budget' => 'integer|min:0',
            'month_11_budget' => 'integer|min:0',
            'month_12_budget' => 'integer|min:0',
            'month_1_expense' => 'integer|min:0',
            'month_2_expense' => 'integer|min:0',
            'month_3_expense' => 'integer|min:0',
            'month_4_expense' => 'integer|min:0',
            'month_5_expense' => 'integer|min:0',
            'month_6_expense' => 'integer|min:0',
            'month_7_expense' => 'integer|min:0',
            'month_8_expense' => 'integer|min:0',
            'month_9_expense' => 'integer|min:0',
            'month_10_expense' => 'integer|min:0',
            'month_11_expense' => 'integer|min:0',
            'month_12_expense' => 'integer|min:0',
        ];
    }
}
