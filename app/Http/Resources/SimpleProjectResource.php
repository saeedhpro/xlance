<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleProjectResource extends JsonResource
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
            'type' => $this->type,
            'verified' => $this->verified,
            'description' => $this->description,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'created_at' => $this->created_at,
        ];
    }
}
