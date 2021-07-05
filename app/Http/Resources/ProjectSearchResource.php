<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectSearchResource extends JsonResource
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
            'description' => $this->description,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'requests_count' => $this->getRequestsCount(),
            'properties' => new PropertyCollectionResource($this->properties),
            'skills' => $this->skills,
            'timeout' => Carbon::parse($this->created_at)->diffInMilliseconds(Carbon::now()),
        ];
    }
}
