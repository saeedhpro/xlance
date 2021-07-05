<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoryUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'as_employer' => $this->as_employer,
            'stories' => new StoryCollectionResource($this->lastStories()),
            'profile' => new ProfileResource($this->profile),
        ];
    }
}
