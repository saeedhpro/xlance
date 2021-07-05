<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawRequest extends Model
{
    use HasFactory;
    protected $table = 'widthraw_requests';
    const CREATED_STATUS = 'created';
    const REJECTED_STATUS = 'rejected';
    const PAYED_STATUS = 'payed';

    protected $fillable = [
        'user_id',
        'status',
        'amount'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
