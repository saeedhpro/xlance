<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioResource extends JsonResource
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
            'title' => $this->title,
            'status' => $this->status,
            'description' => $this->description,
            'skills' => new SkillCollectionResource($this->skills),
            'tags' => $this->getTags(),
            'user' => new SimpleUserResource($this->user),
            'images' => new AssetCollectionResource($this->images),
            'attachments' => new AssetCollectionResource($this->attachments),
        ];
    }
}
