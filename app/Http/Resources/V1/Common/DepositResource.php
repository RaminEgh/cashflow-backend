<?php

namespace App\Http\Resources\V1\Common;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'bank_id' => $this->bank_id,
            'organ_id' => $this->organ_id,
            'branch_code' => $this->branch_code,
            'branch_name' => $this->branch_name,
            'number' => $this->number,
            'sheba' => $this->sheba,
            'type' => $this->type,
            'currency' => $this->currency,
            'description' => $this->description,
            'bank' => new BankResource($this->bank)
        ];
    }
}
