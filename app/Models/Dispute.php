<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_STATUS = 'created';
    const CLOSED_STATUS = 'closed';
    const IN_PROGRESS_STATUS = 'inprogress';

    protected $fillable = [
        'freelancer_id',
        'employer_id',
        'project_id',
        'title',
        'status',
    ];

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id', 'id');
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function chat() {
        return $this->hasOne(DisputeChat::class);
    }
}
