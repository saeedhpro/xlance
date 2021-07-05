<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
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
            'name' => $this->name,
            'path' => $this->url ? $this->url : url('/storage/'.$this->path),
            'user' => $this->user ? $this->user : null,
            'create_at' => $this->created_at,
        ];
    }
}