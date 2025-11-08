<?php

namespace App\Http\Resources\V1\Admin\Allocation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AllocationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'organ' => new OrganResource($item->organ),
                'year' => $item->year,
                'description' => $item->description,
                'month_1_budget' => $item->month_1_budget,
                'month_2_budget' => $item->month_2_budget,
                'month_3_budget' => $item->month_3_budget,
                'month_4_budget' => $item->month_4_budget,
                'month_5_budget' => $item->month_5_budget,
                'month_6_budget' => $item->month_6_budget,
                'month_7_budget' => $item->month_7_budget,
                'month_8_budget' => $item->month_8_budget,
                'month_9_budget' => $item->month_9_budget,
                'month_10_budget' => $item->month_10_budget,
                'month_11_budget' => $item->month_11_budget,
                'month_12_budget' => $item->month_12_budget,
                'month_1_expense' => $item->month_1_expense,
                'month_2_expense' => $item->month_2_expense,
                'month_3_expense' => $item->month_3_expense,
                'month_4_expense' => $item->month_4_expense,
                'month_5_expense' => $item->month_5_expense,
                'month_6_expense' => $item->month_6_expense,
                'month_7_expense' => $item->month_7_expense,
                'month_8_expense' => $item->month_8_expense,
                'month_9_expense' => $item->month_9_expense,
                'month_10_expense' => $item->month_10_expense,
                'month_11_expense' => $item->month_11_expense,
                'month_12_expense' => $item->month_12_expense,
            ];
        });
    }
}
