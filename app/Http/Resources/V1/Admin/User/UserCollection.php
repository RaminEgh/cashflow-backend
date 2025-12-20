<?php

namespace App\Http\Resources\V1\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        return $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'avatar' => $item->avatar,
                'name' => $item->name,
                'email' => $item->email,
                'status' => $item->getStatusName(),
            ];
        });
    }
}
