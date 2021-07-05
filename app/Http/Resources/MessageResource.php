<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'type' => $this->type,
            'file' => new AssetResource($this->file),
            'body' => $this->body,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'user' => new SimpleUserResource($this->user),
            'conversation' => $this->conversation,
        ];
    }
}
