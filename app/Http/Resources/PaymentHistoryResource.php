<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoryResource extends JsonResource
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
            'amount' => $this->amount,
            'user' => new SimpleUserResource($this->user),
            'created_at' => $this->created_at,
        ];
    }
}
