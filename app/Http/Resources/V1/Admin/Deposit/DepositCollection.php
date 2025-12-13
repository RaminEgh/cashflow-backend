<?php

namespace App\Http\Resources\V1\Admin\Deposit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DepositCollection extends ResourceCollection
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
                'bank' => [
                    'id' => $item->bank_id,
                    'name' => $item->bank->name,
                ],
                'organ' => [
                    'id' => $item->organ_id,
                    'name' => $item->organ->name,
                ],
                'branch_code' => $item->branch_code,
                'branch_name' => $item->branch_name,
                'number' => $item->number,
                'balance' => $item->balance,
                'balance_last_synced_at' => $item->balance_last_synced_at,
                'rahkaran_balance' => $item->rahkaran_balance,
                'rahkaran_balance_last_synced_at' => Carbon::parse($item->rahkaran_balance_last_synced_at)->diffForHumans(),
                'sheba' => $item->sheba,
                'type' => [
                    'id' => $item->type->value,
                    'name' => $item->type->name(),
                ],
                'currency' => $item->currency,
                'description' => $item->description,
            ];
        });
    }
}
