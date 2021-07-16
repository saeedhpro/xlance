<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminProjectResource extends JsonResource
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
            'title' => $this->title,
            'status' => $this->status,
            'verified' => $this->verified,
            'type' => $this->type,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'employer' => $this->employer,
            'freelancer' => $this->freelancer,
        ];
    }
}
