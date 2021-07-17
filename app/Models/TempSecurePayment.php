<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSecurePayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'price',
        'user_id',
        'to_id',
        'request_id',
        'project_id',
        'is_first',
    ];
}
