<?php

namespace App\Http\Resources\V1\Admin\Organ;

use App\Http\Resources\V1\Admin\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return $this->collection->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'en_name' => $item->en_name,
                'slug' => $item->slug,
                'phone' => $item->phone,
                'description' => $item->description,
                'admins_id' => UserResource::collection($item->admins),
                'logo' => $item->logo ? url('/api/upload/' . $item->logo . '/display') : null,
                'background' => $item->background ? url('/api/upload/' . $item->background . '/display') : null,
            ];
        });
    }
}
