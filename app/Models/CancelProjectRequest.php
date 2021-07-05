<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelProjectRequest extends Model
{
    use HasFactory;

    const CREATED_STATUS = 'created';
    const ACCEPTED_STATUS = 'accepted';
    const REJECTED_STATUS = 'rejected';

    protected $fillable = [
        'freelancer_id',
        'employer_id',
        'project_id',
        'description',
        'status'
    ];

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id', 'id');
    }
}
