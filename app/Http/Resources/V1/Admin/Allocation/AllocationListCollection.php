<?php

namespace App\Http\Resources\V1\Admin\Allocation;

use App\Http\Resources\V1\Admin\Organ\OrganResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AllocationListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($item) {
            // Calculate total budget (sum of all month budgets)
            $budget = $item->month_1_budget
                + $item->month_2_budget
                + $item->month_3_budget
                + $item->month_4_budget
                + $item->month_5_budget
                + $item->month_6_budget
                + $item->month_7_budget
                + $item->month_8_budget
                + $item->month_9_budget
                + $item->month_10_budget
                + $item->month_11_budget
                + $item->month_12_budget;

            // Calculate total expenses (sum of all month expenses)
            $expenses = $item->month_1_expense
                + $item->month_2_expense
                + $item->month_3_expense
                + $item->month_4_expense
                + $item->month_5_expense
                + $item->month_6_expense
                + $item->month_7_expense
                + $item->month_8_expense
                + $item->month_9_expense
                + $item->month_10_expense
                + $item->month_11_expense
                + $item->month_12_expense;

            return [
                'id' => $item->id,
                'organ' => new OrganResource($item->organ),
                'expenses' => $expenses,
                'budget' => $budget,
                'year' => $item->year,
                'description' => $item->description,
            ];
        })->toArray();
    }
}
