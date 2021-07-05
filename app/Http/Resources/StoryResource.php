<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'user' => new SimpleUserResource($this->user),
            'media' => new AssetResource($this->media),
            'create_at' => $this->create_at,
        ];
    }
}
