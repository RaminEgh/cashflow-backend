<?php

namespace App\Http\Resources\V1\Common;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'url' => $this->url,
            'download_url' => url("/api/upload/{$this->slug}/download"),
            'image_url' => $this->isImage() ? url("/api/upload/{$this->slug}/display") : null,
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }
}
