<?php

namespace App\Models;

use App\Mail\SendResetPasswordMail;
use App\ModelFilters\UserFilter;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\HasApiTokens;
use MannikJ\Laravel\Wallet\Traits\HasWallet;
use Overtrue\LaravelFavorite\Traits\Favoriter;
use Overtrue\LaravelFollow\Followable;
use Overtrue\LaravelLike\Traits\Liker;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions, Followable, HasWallet, Liker, Favoriter, Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'blocked',
        'phone_number',
        'as_employer',
        'number',
        'requests_count',
        'request_package_id',
        'package_expire_date',
        'city_id',
        'country_id',
        'state_id',
        'new_notifications',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function modelFilter()
    {
        return $this->provideFilter(UserFilter::class);
    }

    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new SendResetPasswordMail($this, $token));
    }

    public function rates()
    {
        return $this->hasMany(Rating::class, 'user_id', 'id');
    }

    public function calcRates()
    {
        $rates = $this->rates()->get();
        return (int) $rates->average('rate');
    }

    public function isVerified()
    {
        return $this->email_verified_at != null;
    }

    public function selectedPlans()
    {
        return $this->hasMany(SelectedPlan::class);
    }

    public function selectedPlan()
    {
        $this->deleteOldPlans();
        return $this->selectedPlans()->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>', Carbon::now())->first();
    }

    public function getNumber()
    {
        if($this->selectedPlan()) {
            return $this->selectedPlan()->number;
        } else {
            return $this->number;
        }
    }

    public function requestsCount()
    {
        /** @var SelectedPlan $plan */
        $plan = $this->selectedPlan();
        if($plan) {
            $count = Request::where('user_id', '=', $this->id)
                ->where('created_at', '>', $plan->start_date)
                ->where('created_at', '<=', $plan->end_date)
                ->count();
            if($this->getNumber() == $count) {
                $plan->forceDelete();
                return 0;
            } else {
                return $count;
            }
        } else {
            return $this->requests_count;
        }
    }

    public function histories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function deleteOldPlans()
    {
        $this->selectedPlans()->where('end_date', '<', Carbon::now())->forceDelete();
    }

    public function withdraws()
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    public function conversations() {
        return $this->morphMany(Conversation::class, 'conversationable');
    }

    public function directChats()
    {
        return $this->hasMany(DirectChat::class);
    }

    public function disputeChats()
    {
        return $this->hasMany(DisputeChat::class);
    }

    public function introducer()
    {
        return $this->belongsTo(User::class, 'introducer_id', 'id');
    }

    public function introduces()
    {
        return $this->hasMany(User::class, 'introducer_id', 'id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function acceptedPortfolios()
    {
        return $this->portfolios()->where('status', '=', Portfolio::ACCEPTED_STATUS)->get();
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function uploads()
    {
        return $this->hasMany(Upload::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function sentAcceptRequest()
    {
        return $this->hasMany(AcceptFreelancerRequest::class, 'employer_id', 'id');
    }

    public function recievedAcceptRequest()
    {
        return $this->hasMany(AcceptFreelancerRequest::class, 'freelancer_id', 'id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function availableStories()
    {
        return $this->hasMany(Story::class);
    }

    public function lastStories()
    {
        return $this->stories()->where('created_at', '>', Carbon::now()->subHours(24))->get();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'employer_id', 'id');
    }

    public function ownCreatedProjects()
    {
        return $this->createdProjects()->where('status', '=', Project::CREATED_STATUS);
    }

    public function allCreatedProjects()
    {
        return Project::all()->where('employer_id', '=', $this->id)->where('status', '!=', Project::IN_PAY_STATUS)->where('status', '!=', Project::REJECTED_STATUS);
    }

    public function doingProjects()
    {
        return $this->hasMany(Project::class, 'freelancer_id', 'id');
    }

    public function allProjects()
    {
        return $this->createdProjects()->with(['freelancer'])->where(function ($q) {
            $q->whereIn('status', [
                Project::CREATED_STATUS,
                Project::PUBLISHED_STATUS,
                Project::STARTED_STATUS,
                Project::FINISHED_STATUS,
            ]);
        })->get();
    }

    public function ownDoingProjects()
    {
        return $this->createdProjects()->with(['freelancer'])->where(function ($q) {
            $q->where('selected_request_id', '!=', null);
        })->get();
    }

    public function ownFinishedProjects()
    {
        return $this->createdProjects()->with(['freelancer'])->where(function ($q) {
            $q->whereIn('status', [Project::FINISHED_STATUS, Project::CANCELED_STATUS]);
        })->get();
    }

    public function ownInProgressProjects()
    {
        return $this->createdProjects()->with(['freelancer'])->where(function ($q) {
            $q->whereIn('status', [Project::STARTED_STATUS, Project::DISPUTED_STATUS]);
        })->get();
    }

    public function ownOnlyCreatedProjects()
    {
        return $this->createdProjects()->with(['freelancer'])->where(function ($q) {
            $q->where('status', Project::CREATED_STATUS);
        })->get();
    }

    public function withdrawsAmount()
    {
        return $this->sentSecurePayments()->where('status', '=', SecurePayment::FREE_STATUS)->sum('price');
    }

    public function followedByAuth(): bool
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $this->isFollowedBy($auth);
    }

    public function finishedProjects()
    {
        return $this->doingProjects()->where('status', '=', Project::FINISHED_STATUS);
    }

    public function publishedProjects()
    {
        return $this->createdProjects()->where('status', '=', Project::PUBLISHED_STATUS);
    }

    public function startedProjects()
    {
        return $this->createdProjects()->where('status', '=', Project::STARTED_STATUS);
    }

    public function receivedRequests()
    {
        return $this->hasMany(Request::class, 'to_id', 'id');
    }

    public function sentRequests()
    {
        return $this->hasMany(Request::class, 'user_id', 'id');
    }

    public function receivedSecurePayments()
    {
        return $this->hasMany(SecurePayment::class, 'to_id', 'id');
    }

    public function sentSecurePayments()
    {
        return $this->hasMany(SecurePayment::class, 'user_id', 'id');
    }

    public function notifs()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmark($object)
    {
        if(!$this->hasBookmarked($object)) {
            return $this->bookmarks()->create(['model_type' => get_class($object), 'model_id' => $object->id]);
        }
        return null;
    }

    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_user', 'user_id', 'blocked_id');
    }

    public function requestPackage()
    {
        return $this->belongsTo(RequestPackage::class, 'request_package_id', 'id');
    }

    public function getPackage()
    {
        return $this->selectedPlan();
    }

    public function sentChangePriceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class, 'sender_id', 'id');
    }

    public function receivedChangePriceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class, 'receiver_id', 'id');
    }

    public function hasBlocked($user)
    {
        if($this->blockedUsers()->find($user->id)){
            return true;
        }
        return false;
    }

    public function blockedByMe()
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth) {
            return $auth->hasBlocked($this);
        } else {
            return false;
        }
    }

    public function unmark($object)
    {
        if($this->hasBookmarked($object)) {
            return $this->bookmarks()->where([
                ['bookmarks.model_type', get_class($object)],
                ['bookmarks.model_id', $object->id]
            ])->delete();
        }
    }

    public function hasBookmarked($object)
    {
        return $this->bookmarks()->where([
            ['bookmarks.model_type', get_class($object)],
            ['bookmarks.model_id', $object->id]
        ])->exists();
    }

    public function genResetToken()
    {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 256 )),1,64);
    }

    public function isValidated()
    {
        return  $this->first_name != null &&
                $this->last_name != null &&
                $this->phone_number != null &&
                $this->profile->sheba != null &&
                $this->profile->sheba_accepted == true &&
                $this->profile->national_card_id != null &&
                $this->profile->national_card_accepted == true;
    }

    public function monthlyIncome()
    {
        return $this->sentSecurePayments()->where('status', '=', SecurePayment::FREE_STATUS)->where('created_at', '>=', Carbon::now()->subMonth())->sum('price');
    }

    public function newMessagesCount()
    {
        $conversations = Conversation::all()->filter(function (Conversation $c) {
            return $c->user_id == $this->id || $c->to_id == $this->id;
        })->pluck('id');
        return Message::all()->whereIn('conversation_id', $conversations)
            ->where('user_id', '!=', $this->id)
            ->where('is_system', '==', false)
            ->where('seen', '==', false)->count();
    }
}
