<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangePriceResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'new_price' => $this->new_price,
            'sender' => $this->sender,
            'receiver' => $this->receiver,
            'project' => $this->project,
        ];
    }
}
