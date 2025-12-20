<?php

namespace App\Http\Resources\V1\Admin\Organ;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganResource extends JsonResource
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
            'name' => $this->name,
            'en_name' => $this->en_name,
            'slug' => $this->slug,
            'phone' => $this->phone,
            'description' => $this->description,
            'logo' => url('storage/'.$this->logo),
            'background' => $this->background,
        ];
    }
}
