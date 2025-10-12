<?php

namespace App\Http\Resources\V1\Timeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineGroupedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'date_formatted' => $this->resource['date_formatted'],
            'total_income' => $this->resource['total_income'],
            'total_expense' => $this->resource['total_expense'],
            'net_amount' => $this->resource['net_amount'],
            'entries_count' => $this->resource['entries_count'],
            'entries' => TimelineEntryResource::collection($this->resource['entries']),
        ];
    }
}
