<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotifiableUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name && $this->last_name ? $this->first_name . ' ' . $this->last_name : null,
            'username' => $this->username,
            'email' => $this->email,
            'profile' => new ProfileResource($this->profile),
            'phone_number' => $this->phone_number,
            'as_employer' => $this->as_employer,
            'new_notifications' => $this->new_notifications,
            'created_at' => $this->created_at,
        ];
    }
}
