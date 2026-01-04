<?php

namespace App\Http\Resources\V1\Admin\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
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
        $status = $this->status instanceof UserStatus ? $this->status : UserStatus::from($this->status ?? 0);
        $type = $this->type instanceof UserType ? $this->type : UserType::from($this->type ?? 0);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'national_code' => $this->national_code,
            'phone' => $this->phone,
            'status' => [
                'id' => $status->value,
                'value' => $status->name(),
                'label' => $status->label(),
            ],
            'type' => [
                'id' => $type->value,
                'value' => $type->name(),
                'label' => $type->label(),
            ],
            'roles' => new RoleCollection($this->roles),
            'log' => new UserLogResource($this->sessions()->first()),
        ];
    }
}
