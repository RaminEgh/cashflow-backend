<?php

namespace App\Http\Requests\Admin\Allocation;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllocationRequest extends FormRequest
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
            'organ_id' => 'required|integer|exists:organs,id',
            'year' => 'required|integer|min:1404|max:2050',
            'description' => 'nullable|string|min:3',
            'month_1_budget' => 'nullable|integer|min:0',
            'month_2_budget' => 'nullable|integer|min:0',
            'month_3_budget' => 'nullable|integer|min:0',
            'month_4_budget' => 'nullable|integer|min:0',
            'month_5_budget' => 'nullable|integer|min:0',
            'month_6_budget' => 'nullable|integer|min:0',
            'month_7_budget' => 'nullable|integer|min:0',
            'month_8_budget' => 'nullable|integer|min:0',
            'month_9_budget' => 'nullable|integer|min:0',
            'month_10_budget' => 'nullable|integer|min:0',
            'month_11_budget' => 'nullable|integer|min:0',
            'month_12_budget' => 'nullable|integer|min:0',
            'month_1_expense' => 'nullable|integer|min:0',
            'month_2_expense' => 'nullable|integer|min:0',
            'month_3_expense' => 'nullable|integer|min:0',
            'month_4_expense' => 'nullable|integer|min:0',
            'month_5_expense' => 'nullable|integer|min:0',
            'month_6_expense' => 'nullable|integer|min:0',
            'month_7_expense' => 'nullable|integer|min:0',
            'month_8_expense' => 'nullable|integer|min:0',
            'month_9_expense' => 'nullable|integer|min:0',
            'month_10_expense' => 'nullable|integer|min:0',
            'month_11_expense' => 'nullable|integer|min:0',
            'month_12_expense' => 'nullable|integer|min:0',
        ];
    }
}
