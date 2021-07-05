<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'title' => $this->title,
            'status' => $this->status,
            'type' => $this->type,
            'verified' => $this->verified,
            'description' => $this->description,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'main_conversation_id' => $this->conversation_id,
            'employer' => new SimpleUserResource($this->employer),
            'employer_rate' => new RatingResource($this->employerRate()),
            'freelancer_rate' => new RatingResource($this->freelancerRate()),
            'requests_count' => $this->getRequestsCount(),
            'cancel_request' => new CancelProjectRequestResource($this->cancelRequest()),
            'selected_request' => new RequestResource($this->selectedRequest),
            'request_select_date' => Carbon::parse($this->request_select_date)->diffInDays(Carbon::now()),
            'requested_by_me' => $this->requestedByMe(),
            'accept_freelancer_request' => new AcceptFreelancerResource($this->acceptFreelancerRequest),
            'requests' => new RequestCollectionResource($this->requests),
            'properties' => new PropertyCollectionResource($this->properties),
            'skills' => $this->skills,
            'freelancer' => new SimpleUserResource($this->freelancer),
            'attachments' => new AssetCollectionResource($this->attachments),
            'created_at' => $this->created_at,
            'timeout' => Carbon::parse($this->created_at)->diffInMilliseconds(Carbon::now()),
        ];
    }
}
