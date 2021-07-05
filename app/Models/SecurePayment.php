<?php

namespace App\Models;

use App\ModelFilters\SecurePaymentFilter;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurePayment extends Model
{
    use HasFactory, Filterable;

    const CREATED_STATUS = "created";
    const ACCEPTED_STATUS = "accepted";
    const REJECTED_STATUS = "rejected";
    const PAYED_STATUS = "payed";
    const CANCELED_STATUS = "canceled";
    const FREE_STATUS = "free";

    protected $fillable = [
        'title',
        'price',
        'status',
        'user_id',
        'to_id',
        'request_id',
        'project_id',
        'is_first',
    ];

    public function modelFilter()
    {
        return $this->provideFilter(SecurePaymentFilter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id', 'id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id', 'id');
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
