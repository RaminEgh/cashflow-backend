<?php

namespace App\Http\Resources\V1\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ip_address' => $this->ip_address,
            'last_activity' => $this->last_activity,
            'user_agent' => $this->user_agent,
            'description' => $this->description,
            'type' => $this->type,
        ];
    }
}
