<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'text' => $this->text,
            'type' => $this->type,
            'content' => $this->getContent(),
            'user' => new NotificationUserResource($this->user),
            'image' => new AssetResource($this->image),
            'title' => $this->title,
            'notifiable_id' => $this->notifiable_id,
            'notifiable_type' => $this->notifiable_type,
            'created_at' => $this->created_at
        ];
    }
}
