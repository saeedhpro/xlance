<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'number' => $this->number,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'plan' => new RequestPackageResource($this->plan),
            'user' => new SimpleUserResource($this->user),
            'is_monthly' => $this->is_monthly
        ];
    }
}
