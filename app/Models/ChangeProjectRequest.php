<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeProjectRequest extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_STATUS = 'created';
    const ACCEPTED_STATUS = 'accepted';
    const REJECTED_STATUS = 'rejected';

    const FREELANCER_TYPE = 'freelancer';
    const EMPLOYER_TYPE = 'employer';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'project_id',
        'status',
        'new_price',
        'type',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
