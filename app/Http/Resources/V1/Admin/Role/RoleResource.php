<?php

namespace App\Http\Resources\V1\Admin\Role;

use App\Http\Resources\V1\Admin\Permission\PermissionCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'slug' => $this->slug,
            'label' => $this->label,
            'description' => $this->description,
            'permissions' => new PermissionCollection($this->permissions)
        ];
    }
}
