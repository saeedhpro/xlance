<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperienceResource extends JsonResource
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
            'position' => $this->position,
            'company' => $this->company,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'up_to_now' => $this->up_to_now,
            'description' => $this->description,
            'user' => new SimpleUserResource($this->user),
        ];
    }
}
