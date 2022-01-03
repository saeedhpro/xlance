<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'bio' => $this->bio,
            'description' => $this->description,
            'birth_date' => $this->birth_date,
            'marital_status' => $this->marital_status,
            'languages' => $this->getLanguages(),
            'national_card' => new AssetResource($this->nationalCard()->first()),
            'national_card_accepted' => $this->national_card_accepted,
            'sheba' => $this->sheba,
            'sheba_accepted' => $this->sheba_accepted,
            'avatar_accepted' => $this->avatar_accepted,
            'bg_accepted' => $this->bg_accepted,
            'avatar' => new AssetResource($this->avatar()->first()),
            'new_avatar' => new AssetResource($this->newAvatar()->first()),
            'bg' => new AssetResource($this->background()->first()),
            'new_bg' => new AssetResource($this->newBackground()->first()),
            'new_national_card' => new AssetResource($this->newNationalCard()->first()),
            'new_national_card_id' => $this->new_national_card_id
        ];
    }
}
