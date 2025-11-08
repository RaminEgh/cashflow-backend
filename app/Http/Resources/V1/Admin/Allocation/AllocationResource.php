<?php

namespace App\Http\Resources\V1\Admin\Allocation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'description' => $this->description,
            'month_1' => [
                'budget' => $this->month_1_budget,
                'expense' => $this->month_1_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_2' => [
                'budget' => $this->month_2_budget,
                'expense' => $this->month_2_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_3' => [
                'budget' => $this->month_3_budget,
                'expense' => $this->month_3_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_4' => [
                'budget' => $this->month_4_budget,
                'expense' => $this->month_4_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_5' => [
                'budget' => $this->month_5_budget,
                'expense' => $this->month_5_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_6' => [
                'budget' => $this->month_6_budget,
                'expense' => $this->month_6_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_7' => [
                'budget' => $this->month_7_budget,
                'expense' => $this->month_7_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_8' => [
                'budget' => $this->month_8_budget,
                'expense' => $this->month_8_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_9' => [
                'budget' => $this->month_9_budget,
                'expense' => $this->month_9_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_10' => [
                'budget' => $this->month_10_budget,
                'expense' => $this->month_10_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_11' => [
                'budget' => $this->month_11_budget,
                'expense' => $this->month_11_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],
            'month_12' => [
                'budget' => $this->month_12_budget,
                'expense' => $this->month_12_expense,
                'income' => 1_200_000_000,
                'outcome' => 1_400_000_000,
            ],

        ];
    }
}
