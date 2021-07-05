<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
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
            'status' => $this->status,
            'type' => $this->type,
            'new_messages_count' => $this->newMessages(),
            'user' => new UserResource($this->user),
            'to' => new UserResource($this->to),
            'project' => new ProjectResource($this->project),
        ];
    }
}
