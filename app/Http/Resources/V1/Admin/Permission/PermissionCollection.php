<?php

namespace App\Http\Resources\V1\Admin\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PermissionCollection extends ResourceCollection
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
                'label' => $item->label,
                'slug' => $item->slug,
                'description' => $item->description,
            ];
        });
    }
}
