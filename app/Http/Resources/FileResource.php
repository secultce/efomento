<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'grp' => $this->grp,
            'description' => $this->description,
            'path' => $this->path,
            'url' => $this->url,
            'private' => $this->private,
            'created_at' => $this->created_at,
        ];
    }
}
