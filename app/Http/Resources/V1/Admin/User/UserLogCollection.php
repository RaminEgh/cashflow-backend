<?php

namespace App\Http\Resources\V1\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserLogCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function ($item) {
            return [
                'ip_address' => $item->ip_address,
                'user_agent' => $item->user_agent,
                'type' => $item->type,
                'description' => $item->description,
                'date' => $item->last_activity,
            ];
        });    }
}
