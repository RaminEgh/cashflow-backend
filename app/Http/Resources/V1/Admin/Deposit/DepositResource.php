<?php

namespace App\Http\Resources\V1\Admin\Deposit;

use App\Http\Resources\V1\Admin\Organ\OrganResource;
use App\Http\Resources\V1\Common\BankResource;
use App\Models\Deposit;
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
            'type' => (array_column(Deposit::DEPOSITS_KEY_VALUE, null, 'id'))[$this->type],
            'currency' => $this->currency,
            'description' => $this->description,
            'balance' => $this->balance,
            'rahkaran_balance' => $this->rahkaran_balance,
            'balance_last_synced_at' => Carbon::parse($this->balance_last_synced_at)->diffForHumans(),
            'rahkaran_balance_last_synced_at' => Carbon::parse($this->rahkaran_balance_last_synced_at)->diffForHumans(),
        ];
    }
}
