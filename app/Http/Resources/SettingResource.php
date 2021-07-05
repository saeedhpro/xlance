<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'project_price' => $this->project_price,
            'distinguished_price' => $this->distinguished_price,
            'sponsored_price' => $this->sponsored_price,
        ];
    }
}
