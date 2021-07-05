<?php

namespace App\Models;

use App\Http\Resources\ProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    const OPEN_STATUS = 'open';
    const CLOSED_STATUS = 'close';

    const DIRECT_TYPE = 'direct_chat';
    const PROJECT_TYPE = 'project_chat';
    const DISPUTE_TYPE = 'dispute_chat';

    protected $fillable = [
        'status',
        'type',
        'user_id',
        'project_id',
        'to_id',
        'conversationable_id',
        'conversationable_type',
        'new_messages_count'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function to() {
        return $this->belongsTo(User::class, 'to_id', 'id');
    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
    * Get the parent commentable model (post or video).
    */
    public function conversationable(){
        return $this->morphTo();
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function newMessages()
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth) {
            return $this->messages()->where('user_id', '!=', $auth->id)
                ->where('seen', '==', false)
                ->where('is_system', '==', false)->count();
        } else {
            return 0;
        }
    }

    public function isDisabled()
    {
        /** @var Project $project */
        $project = $this->project;
        if($project != null) {
            /** @var User $auth */
            $auth = auth()->user();
            $employer = $project->employer;
            $freelancer = $project->freelancer;
            $emp_count = $this->messages()->where('user_id', '=', $employer->id)->count() == 0;
            if(!$freelancer) {
                if($auth->id == $employer->id) {
                    return false;
                } else {
                    if($auth->id == $this->user->id) {
                        if(!$emp_count) {
                            return false;
                        } else {
                            return true;
                        }
                    } else {
                        return !$auth->hasRole('admin');
                    }
                }
            } else {
                if(!($emp_count && $auth->id == $freelancer->id)) {
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            return false;
        }
    }
}
