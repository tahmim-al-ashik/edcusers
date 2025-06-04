<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadbandDbProfile extends Model
{
    use HasFactory;
    protected $connection = 'mikrotikDb';
    protected $table = 'profile';
    public $timestamps = false;
}
