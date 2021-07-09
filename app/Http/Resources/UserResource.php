<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name && $this->last_name ? $this->first_name . ' ' . $this->last_name : null,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'as_employer' => $this->as_employer,
            'new_notifications' => $this->new_notifications,
            'validated' => $this->isValidated(),
            'number' => $this->getNumber(),
            'requests_count' => $this->requestsCount(),
            'package' => new PackageResource($this->getPackage()),
            'requests' => $this->sentRequests,
            'package_expire_date' => $this->package_expire_date,
            'withdraws_amount' => $this->withdrawsAmount(),
            'followed' => $this->followedByAuth(),
            'followers_count' => $this->followers()->count(),
            'followings_count' => $this->followings()->count(),
            'blocked_by_admin' => $this->blocked,
            'blocked' => $this->blockedByMe(),
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'monthly_income' => $this->monthlyIncome(),
            'rate' => $this->calcRates(),
            'rates' => new RatingCollectionResource($this->rates),
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getPermissionsViaRoles()->pluck('name'),
            'verified' => $this->isVerified(),
            'balance' => $this->wallet->balance,
            'profile' => new ProfileResource($this->profile),
            'finished_projects' => $this->finishedProjects()->count(),
            'published_projects' => $this->publishedProjects()->count(),
            'doing_projects' => $this->doingProjects()->count(),
            'own_doing_projects' => $this->ownDoingProjects()->count(),
            'created_projects' => $this->ownCreatedProjects()->count(),
            'all_projects' => $this->allCreatedProjects()->count(),
            'experiences' => new ExperienceCollectionResource($this->experiences),
            'educations' => new EducationCollectionResource($this->educations),
            'achievements' => new AchievementCollectionResource($this->achievements),
            'portfolios' => new PortfolioCollectionResource($this->acceptedPortfolios()),
            'skills' => new SkillCollectionResource($this->skills),
            'created_at' => $this->created_at,
        ];
    }
}
