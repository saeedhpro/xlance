<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;

    const WITHDRAW_TYPE = 'withdraw';
    const DEPOSIT_TYPE = 'deposit';

    const CREATED_STATUS = 'created';
    const REJECTED_STATUS = 'rejected';
    const PAYED_STATUS = 'payed';
    const DEPOSITED_STATUS = 'deposited';

    protected $fillable = [
        'user_id',
        'history_id',
        'amount',
        'type',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
