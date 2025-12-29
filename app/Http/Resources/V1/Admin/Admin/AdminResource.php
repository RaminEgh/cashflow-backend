<?php

namespace App\Http\Resources\V1\Admin\Admin;

use App\Http\Resources\V1\Admin\Role\RoleCollection;
use App\Http\Resources\V1\Admin\User\UserLogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'national_code' => $this->national_code,
            'phone' => $this->phone,
            'status' => [
                'id' => $this->status->value,
                'value' => $this->status->name(),
                'label' => $this->status->label(),
            ],
            'type' => [
                'id' => $this->type->value,
                'value' => $this->type->name(),
                'label' => $this->type->label(),
            ],
            'roles' => new RoleCollection($this->roles),
            'log' => new UserLogResource($this->sessions()->first()),
        ];
    }
}
