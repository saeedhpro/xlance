<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'color',
        'title',
        'type',
        'description',
        'price',
        'number'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
