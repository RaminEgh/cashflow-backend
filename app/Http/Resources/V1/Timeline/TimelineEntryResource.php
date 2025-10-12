<?php

namespace App\Http\Resources\V1\Timeline;

use App\Http\Resources\V1\Admin\Organ\OrganResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEntryResource extends JsonResource
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
            'type' => $this->type,
            'type_name' => $this->getTypeName(),
            'title' => $this->title,
            'date' => $this->date->format('Y-m-d'),
            'amount' => $this->amount,
            'organ' => new OrganResource($this->whenLoaded('organ')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
