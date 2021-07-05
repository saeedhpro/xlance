<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumeResource extends JsonResource
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
            'position' => $this->position,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'birth_Date' => $this->birth_Date,
            'languages' => $this->listLanguages(),
            'user' => $this->user,
            'experiences' => new ExperienceCollectionResource($this->experiences),
            'educations' => new EducationCollectionResource($this->educations),
            'achievements' => new AchievementCollectionResource($this->achievements),

        ];
    }
}
