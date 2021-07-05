<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SimpleUserResource;

class RatingResource extends JsonResource
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
            'rate' => $this->rate,
            'description' => $this->description,
            'user' => new SimpleUserResource($this->user),
            'rater' => new SimpleUserResource($this->rater),
            'project' => new SimpleProjectResource($this->project),
            'created_at' => $this->created_at,
        ];
    }
}
