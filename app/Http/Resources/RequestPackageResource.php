<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestPackageResource extends JsonResource
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
            'color' => $this->color,
            'title' => $this->title,
            'number' => $this->number,
            'description' => $this->description,
            'price' => $this->price,
            'users_count' => $this->users()->count(),
        ];
    }
}
