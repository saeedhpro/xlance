<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SimpleUserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name && $this->last_name ? $this->first_name . ' ' . $this->last_name : null,
            'username' => $this->username,
            'email' => $this->email,
            'followed' => $this->followedByAuth(),
            'roles' => $this->getRoleNames(),
            'monthly_income' => $this->monthlyIncome(),
            'validated' => $this->isValidated(),
            'number' => $this->getNumber(),
            'requests_count' => $this->requestsCount(),
            'rate' => $this->calcRates(),
            'balance' => $this->wallet->balance,
            'profile' => new ProfileResource($this->profile),
            'created_at' => $this->created_at,
        ];
    }
}
