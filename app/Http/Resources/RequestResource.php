<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'price' => $this->price,
            'delivery_date' => $this->delivery_date,
            'description' => $this->description,
            'is_distinguished' => $this->is_distinguished,
            'is_sponsored' => $this->is_sponsored,
            'project' => $this->project,
            'user' => new SimpleUserResource($this->user),
            'to' => new SimpleUserResource($this->to),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
