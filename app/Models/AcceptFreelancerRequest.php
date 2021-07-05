<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcceptFreelancerRequest extends Model
{
    use HasFactory;

    const CREATED_STATUS = 'created';
    const REJECTED_STATUS = 'rejected';
    const ACCEPTED_STATUS = 'accepted';

    protected $fillable = [
        'employer_id',
        'freelancer_id',
        'project_id',
        'request_id',
        'status',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id', 'id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    }
}
