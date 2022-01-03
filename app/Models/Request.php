<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Request extends Model
{
    use HasFactory;
    const CREATED_STATUS = "created";
    const ACCEPTED_STATUS = "accepted";
    const STARTED_STATUS = "started";
    const FINISHED_STATUS = "finished";
    const REJECTED_STATUS = "rejected";
    const IN_PAY_STATUS = "inpay";

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_id', 'id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function securePayments(): HasMany
    {
        return $this->hasMany(SecurePayment::class);
    }

    public function tempSecurePayments(): HasMany
    {
        return $this->hasMany(TempSecurePayment::class);
    }
}
