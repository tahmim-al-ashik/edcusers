<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WifiDbUserInfoClient extends Model
{
    use HasFactory;

    // Database Connection
    protected $connection = 'clientRadiusDb';
    protected $table = 'userinfo';
    public $timestamps = false;
}
