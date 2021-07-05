<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationResource extends JsonResource
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
            'degree' => $this->degree,
            'school_name' => $this->school_name,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'up_to_now' => $this->up_to_now,
            'user' => new SimpleUserResource($this->user),
            'description' => $this->description,
        ];
    }
}
