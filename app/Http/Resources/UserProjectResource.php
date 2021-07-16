<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProjectResource extends JsonResource
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
            'freelancer' => new ConversationUserResource($this->freelancer),
            'selected_request' => new UserProjectRequestResource($this->selectedRequest),
            'created_at' => $this->created_at,
        ];
    }
}
