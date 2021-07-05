<?php

namespace App\Models;

use App\ModelFilters\ProjectFilter;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, Filterable;

    const CREATED_STATUS = 'created';
    const STARTED_STATUS = 'started';
    const IN_PAY_STATUS = 'inpay';
    const PUBLISHED_STATUS = 'published';
    const CANCELED_STATUS = 'canceled';
    const REJECTED_STATUS = 'rejected';
    const FINISHED_STATUS = 'finished';
    const DISPUTED_STATUS = 'disputed';

    const PUBLIC_TYPE = 'public';
    const SPECIAL_TYPE = 'special';

    protected $fillable = [
        'title',
        'verified',
        'status',
        'type',
        'description',
        'min_price',
        'max_price',
        'employer_id',
        'freelancer_id',
        'selected_request_id',
        'conversation_id',
        'request_select_date',
    ];

    public function modelFilter()
    {
        return $this->provideFilter(ProjectFilter::class);
    }

    public function mainConversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }

    public function rate()
    {
        return $this->hasMany(Rating::class, 'project_id', 'id');
    }

    public function employerRate()
    {
        return $this->rate()->where('rater_id', '=', $this->employer->id)->first();
    }

    public function freelancerRate()
    {
        return $this->freelancer ? $this->rate()->where('rater_id', '=', $this->freelancer->id)->first() : null;
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id', 'id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id', 'id');
    }

    public function acceptFreelancerRequest()
    {
        return $this->hasOne(AcceptFreelancerRequest::class, 'project_id', 'id');
    }

    public function selectedRequest()
    {
        return $this->belongsTo(Request::class, 'selected_request_id', 'id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function properties()
    {
        return $this->belongsToMany(ProjectProperty::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function priceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class);
    }

    public function createdPriceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class)->where('status', '=', ChangeProjectRequest::CREATED_STATUS);
    }

    public function acceptedPriceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class)->where('status', '=', ChangeProjectRequest::ACCEPTED_STATUS);
    }

    public function rejectedPriceRequests()
    {
        return $this->hasMany(ChangeProjectRequest::class)->where('status', '=', ChangeProjectRequest::REJECTED_STATUS);
    }

    public function chat() {
        return $this->hasOne(ProjectChat::class);
    }

    public function payments() {
        return $this->hasMany(SecurePayment::class);
    }

    public function getRequestsCount()
    {
        return $this->requests()->count();
    }

    public function requestedByMe()
    {
        /** @var User $auth */
        $auth = auth()->user();
        return $auth && $this->requests()->where('user_id', '=', $auth->id)->count() > 0;
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function cancelRequests()
    {
        return $this->hasMany(CancelProjectRequest::class, 'project_id', 'id');
    }

    public function cancelRequest()
    {
        return $this->hasMany(CancelProjectRequest::class, 'project_id', 'id')->where('status', '=', CancelProjectRequest::CREATED_STATUS)->first();
    }
}
