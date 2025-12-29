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
                'has_access_banking_api' => $item->has_access_banking_api,
                'balance' => $item->balance,
                'balance_synced_at' => $item->balance_synced_at ? Carbon::parse($item->balance_synced_at)->diffForHumans() : null,
                'rahkaran_balance' => $item->rahkaran_balance,
                'rahkaran_synced_at' => $item->rahkaran_synced_at ? Carbon::parse($item->rahkaran_synced_at)->diffForHumans() : null,
                'last_balance_sync_success' => $item->last_balance_sync_success,
                'last_rahkaran_sync_success' => $item->last_rahkaran_sync_success,
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
