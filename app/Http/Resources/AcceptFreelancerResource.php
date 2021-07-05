<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AcceptFreelancerResource extends JsonResource
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
            'freelancer' => new SimpleUserResource($this->freelancer),
            'employer' => new SimpleUserResource($this->employer),
            'project' => new SimpleProjectResource($this->project),
            'request' => new RequestResource($this->request),
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
