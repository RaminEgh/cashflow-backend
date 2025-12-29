<?php

namespace App\Http\Resources\V1\Admin\Deposit;

use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Common\BankResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class DepositResource extends JsonResource
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
            'organ' => new OrganResource($this->organ),
            'bank' => new BankResource($this->bank),
            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,
            'number' => $this->number,
            'sheba' => $this->sheba,
            'type' => [
                'id' => $this->type->value,
                'name' => $this->type->name(),
            ],
            'currency' => $this->currency,
            'description' => $this->description,
            'has_access_banking_api' => (bool) $this->has_access_banking_api,
            'balance' => $this->balance,
            'rahkaran_balance' => $this->rahkaran_balance,
            'balance_synced_at' => $this->balance_synced_at ? Carbon::parse($this->balance_synced_at)->diffForHumans() : null,
            'rahkaran_synced_at' => $this->rahkaran_synced_at ? Carbon::parse($this->rahkaran_synced_at)->diffForHumans() : null,
        ];
    }
}
