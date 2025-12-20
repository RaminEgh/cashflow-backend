<?php

namespace App\Http\Resources\V1\Common;

use App\Http\Resources\V1\Admin\Permission\PermissionCollection;
use App\Http\Resources\V1\Admin\Role\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'national_code' => $this->national_code,
            'type' => $this->getTypeName(),
            'type_id' => $this->type,
            'status' => $this->getStatusName(),
            'status_id' => $this->status,
            'logged_at' => $this->logged_at?->toIso8601String(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => new PermissionCollection($this->permissions()),
        ];
    }
}
