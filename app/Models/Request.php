<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    const CREATED_STATUS = "created";
    const ACCEPTED_STATUS = "accepted";
    const STARTED_STATUS = "started";
    const FINISHED_STATUS = "finished";
    const REJECTED_STATUS = "rejected";

    const FREELANCER_TYPE = "freelancer";
    const EMPLOYER_TYPE = "employer";

    protected $fillable = [
        'title',
        'type',
        'price',
        'status',
        'delivery_date',
        'description',
        'project_id',
        'is_distinguished',
        'is_sponsored',
        'user_id',
        'to_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function securePayments()
    {
        return $this->hasMany(SecurePayment::class);
    }
}
