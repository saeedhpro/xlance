<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const CREATED_STATUS = 'created';
    const PAYED_STATUS = 'payed';
    const CANCELED_STATUS = 'canceled';

    const PROJECT_TYPE = 'project';
    const DEPOSIT_TYPE = 'deposit';
    const PACKAGE_TYPE = 'package';
    const SECURE_PAYMENT_TYPE = 'secure_payment';

    protected $fillable = [
        'amount',
        'withdraw_amount',
        'status',
        'type',
        'is_monthly',
        'transaction_id',
        'request_package_id',
        'user_id',
        'project_id',
        'secure_payment_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(RequestPackage::class, 'request_package_id', 'id');
    }

    public function securePayment()
    {
        return $this->belongsTo(SecurePayment::class, 'secure_payment_id', 'id');
    }
}
