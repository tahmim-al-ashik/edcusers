<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLatlong extends Model
{
    use HasFactory;
    protected $table = 'user_lat_long';
    protected $fillable= [
        'uid',
        'mobile_number',
        'name',
        'latitude',
        'longitude',
        'status',
        'created_at',
        'updated_at'
    ];
}
