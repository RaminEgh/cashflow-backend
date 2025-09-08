<?php

namespace App\Http\Resources\V1\Admin\Organ;

use App\Http\Resources\V1\Admin\Deposit\DepositCollection;
use App\Http\Resources\V1\Admin\User\UserResource;
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

            'month_1_budget' => $this->month_1_budget,
            'month_2_budget' => $this->month_2_budget,
            'month_3_budget' => $this->month_3_budget,
            'month_4_budget' => $this->month_4_budget,
            'month_5_budget' => $this->month_5_budget,
            'month_6_budget' => $this->month_6_budget,
            'month_7_budget' => $this->month_7_budget,
            'month_8_budget' => $this->month_8_budget,
            'month_9_budget' => $this->month_9_budget,
            'month_10_budget' => $this->month_10_budget,
            'month_11_budget' => $this->month_11_budget,
            'month_12_budget' => $this->month_12_budget,

            'month_1_expense' => $this->month_1_expense,
            'month_2_expense' => $this->month_2_expense,
            'month_3_expense' => $this->month_3_expense,
            'month_4_expense' => $this->month_4_expense,
            'month_5_expense' => $this->month_5_expense,
            'month_6_expense' => $this->month_6_expense,
            'month_7_expense' => $this->month_7_expense,
            'month_8_expense' => $this->month_8_expense,
            'month_9_expense' => $this->month_9_expense,
            'month_10_expense' => $this->month_10_expense,
            'month_11_expense' => $this->month_11_expense,
            'month_12_expense' => $this->month_12_expense,
        ];
    }
}
